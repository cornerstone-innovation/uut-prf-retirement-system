<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use App\Application\Services\Plan\PlanUnitResolverService;

class BuildPlanNavSnapshotDataService
{
    public function __construct(
        private readonly PlanUnitResolverService $planUnitResolverService
    ) {
    }

    public function build(Plan $plan, string $valuationDate): array
    {
        $plan->loadMissing([
            'equityHoldings.marketSecurity.priceSnapshots',
            'bondHoldings',
            'cashPositions',
            'configuration',
        ]);

        $valuationDateCarbon = Carbon::parse($valuationDate)->startOfDay();

        $activeEquityHoldings = $plan->equityHoldings()
            ->where('holding_status', 'active')
            ->with('marketSecurity.priceSnapshots')
            ->get();

        $activeBondHoldings = $plan->bondHoldings()
            ->where('holding_status', 'active')
            ->get();

        $cashPositions = $plan->cashPositions()
            ->whereDate('position_date', '<=', $valuationDateCarbon->toDateString())
            ->get();

        $equityBreakdown = [];
        $equityMarketValue = 0.0;

        foreach ($activeEquityHoldings as $holding) {
            $marketSecurity = $holding->marketSecurity;

            if (! $marketSecurity) {
                continue;
            }

        $priceSnapshot = $marketSecurity->priceSnapshots()
            ->whereDate('valuation_date', $valuationDateCarbon->toDateString())
            ->latest('id')
            ->first();

            if (! $priceSnapshot) {
                throw ValidationException::withMessages([
                    'market_price' => [
                        "Missing price snapshot for {$marketSecurity->symbol} on {$valuationDateCarbon->toDateString()}."
                    ],
                ]);
            }

            $quantity = (float) $holding->quantity;
            $marketPrice = (float) ($priceSnapshot->market_price ?? 0);
            $marketValue = round($quantity * $marketPrice, 2);

            $equityMarketValue += $marketValue;

            $equityBreakdown[] = [
                'holding_id' => $holding->id,
                'symbol' => $marketSecurity->symbol,
                'company_name' => $marketSecurity->company_name,
                'quantity' => $quantity,
                'market_price' => $marketPrice,
                'market_value' => $marketValue,
                'price_snapshot_id' => $priceSnapshot->id,
                'price_snapshot_date' => $priceSnapshot->valuation_date,
            ];
        }

        $bondBreakdown = [];
        $bondMarketValue = 0.0;
        $bondAccruedInterest = 0.0;

        foreach ($activeBondHoldings as $bondHolding) {
            $marketValue = (float) ($bondHolding->market_value ?? 0);
            $accruedInterest = (float) ($bondHolding->accrued_interest ?? 0);

            $bondMarketValue += $marketValue;
            $bondAccruedInterest += $accruedInterest;

            $bondBreakdown[] = [
                'holding_id' => $bondHolding->id,
                'instrument_name' => $bondHolding->instrument_name,
                'face_value' => (float) ($bondHolding->face_value ?? 0),
                'market_value' => $marketValue,
                'accrued_interest' => $accruedInterest,
            ];
        }

        $cashValue = round(
            $cashPositions->sum(fn ($row) => (float) $row->cash_amount),
            2
        );

        $unitSummary = $this->planUnitResolverService->getUnitSummary($plan);
        $outstandingUnits = (float) ($unitSummary['issued_units'] ?? 0);

        if ($outstandingUnits <= 0) {
            throw ValidationException::withMessages([
                'outstanding_units' => ['Outstanding units must be greater than zero to calculate NAV.'],
            ]);
        }

        $totalGrossAssetValue = round(
            $equityMarketValue + $bondMarketValue + $bondAccruedInterest + $cashValue,
            2
        );

        $totalLiabilities = 0.0;
        $netAssetValue = round($totalGrossAssetValue - $totalLiabilities, 2);
        $navPerUnit = round($netAssetValue / $outstandingUnits, 6);

        return [
            'plan_id' => $plan->id,
            'valuation_date' => $valuationDateCarbon->toDateString(),
            'plan_family' => $plan->configuration?->plan_family,
            'equity_market_value' => round($equityMarketValue, 2),
            'bond_market_value' => round($bondMarketValue, 2),
            'bond_accrued_interest' => round($bondAccruedInterest, 2),
            'cash_value' => round($cashValue, 2),
            'total_gross_asset_value' => $totalGrossAssetValue,
            'total_liabilities' => round($totalLiabilities, 2),
            'net_asset_value' => $netAssetValue,
            'outstanding_units' => round($outstandingUnits, 6),
            'nav_per_unit' => $navPerUnit,
            'price_source' => 'dse_closing_snapshot',
            'calculation_status' => 'calculated',
            'breakdown' => [
                'equities' => $equityBreakdown,
                'bonds' => $bondBreakdown,
                'cash_positions_count' => $cashPositions->count(),
            ],
        ];
    }
}