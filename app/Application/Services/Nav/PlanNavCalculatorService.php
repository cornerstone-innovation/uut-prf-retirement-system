<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use App\Models\MarketSecurityPriceSnapshot;
use App\Application\Services\Plan\PlanUnitResolverService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PlanNavCalculatorService
{
    public function __construct(
        private readonly PlanUnitResolverService $planUnitResolverService,
    ) {
    }

    public function calculate(Plan $plan, string $valuationDate): array
    {
        $plan->loadMissing([
            'configuration',
            'equityHoldings.marketSecurity',
            'bondHoldings',
            'cashPositions',
        ]);

        $planFamily = $plan->configuration?->plan_family ?? 'generic';
        $holdingScope = $plan->configuration?->holding_scope ?? 'equity_only';
        $valuationDateCarbon = Carbon::parse($valuationDate)->startOfDay();
        $cashPositionsCount = $plan->cashPositions()
            ->whereDate('position_date', '<=', $valuationDateCarbon->toDateString())
            ->count();

        return match ($planFamily) {
            'youngsters' => $this->calculateYoungsters(
                $plan,
                $valuationDateCarbon,
                $holdingScope,
                $cashPositionsCount
            ),
            'middle_agers' => $this->calculateMiddleAgers(
                $plan,
                $valuationDateCarbon,
                $holdingScope,
                $cashPositionsCount
            ),
            default => $this->calculateGeneric(
                $plan,
                $valuationDateCarbon,
                $holdingScope,
                $cashPositionsCount
            ),
        };
    }

    private function calculateYoungsters(
        Plan $plan,
        Carbon $valuationDate,
        string $holdingScope,
        int $cashPositionsCount
    ): array {
        $equity = $this->resolveEquityValue($plan, $valuationDate, $holdingScope);
        $cashValue = $this->resolveCashValue($plan, $valuationDate);
        $liabilities = $this->resolveLiabilities($plan, $valuationDate);
        $outstandingUnits = $this->resolveOutstandingUnits($plan);

        $grossAssetValue = round(
            $equity['total_market_value'] + $cashValue,
            2
        );

        $netAssetValue = round($grossAssetValue - $liabilities, 2);
        $navPerUnit = round($netAssetValue / $outstandingUnits, 6);

        return [
            'plan_id' => $plan->id,
            'valuation_date' => $valuationDate->toDateString(),
            'plan_family' => 'youngsters',
            'equity_market_value' => round($equity['total_market_value'], 2),
            'bond_market_value' => 0.0,
            'bond_accrued_interest' => 0.0,
            'cash_value' => round($cashValue, 2),
            'total_gross_asset_value' => $grossAssetValue,
            'total_liabilities' => round($liabilities, 2),
            'net_asset_value' => $netAssetValue,
            'outstanding_units' => round($outstandingUnits, 6),
            'nav_per_unit' => $navPerUnit,
            'price_source' => 'dse_closing_snapshot',
            'calculation_status' => 'calculated',
            'breakdown' => [
                'equities' => $equity['breakdown'],
                'bonds' => [],
                'cash_positions_count' => $cashPositionsCount,
            ],
        ];
    }

    private function calculateMiddleAgers(
        Plan $plan,
        Carbon $valuationDate,
        string $holdingScope,
        int $cashPositionsCount
    ): array {
        $equity = $this->resolveEquityValue($plan, $valuationDate, $holdingScope);
        $bond = $this->resolveBondValue($plan, $valuationDate, $holdingScope);
        $cashValue = $this->resolveCashValue($plan, $valuationDate);
        $liabilities = $this->resolveLiabilities($plan, $valuationDate);
        $outstandingUnits = $this->resolveOutstandingUnits($plan);

        $grossAssetValue = round(
            $equity['total_market_value']
            + $bond['total_market_value']
            + $bond['total_accrued_interest']
            + $cashValue,
            2
        );

        $netAssetValue = round($grossAssetValue - $liabilities, 2);
        $navPerUnit = round($netAssetValue / $outstandingUnits, 6);

        return [
            'plan_id' => $plan->id,
            'valuation_date' => $valuationDate->toDateString(),
            'plan_family' => 'middle_agers',
            'equity_market_value' => round($equity['total_market_value'], 2),
            'bond_market_value' => round($bond['total_market_value'], 2),
            'bond_accrued_interest' => round($bond['total_accrued_interest'], 2),
            'cash_value' => round($cashValue, 2),
            'total_gross_asset_value' => $grossAssetValue,
            'total_liabilities' => round($liabilities, 2),
            'net_asset_value' => $netAssetValue,
            'outstanding_units' => round($outstandingUnits, 6),
            'nav_per_unit' => $navPerUnit,
            'price_source' => 'dse_closing_snapshot',
            'calculation_status' => 'calculated',
            'breakdown' => [
                'equities' => $equity['breakdown'],
                'bonds' => $bond['breakdown'],
                'cash_positions_count' => $cashPositionsCount,
            ],
        ];
    }

    private function calculateGeneric(
        Plan $plan,
        Carbon $valuationDate,
        string $holdingScope,
        int $cashPositionsCount
    ): array {
        $equity = $this->resolveEquityValue($plan, $valuationDate, $holdingScope);
        $bond = $this->resolveBondValue($plan, $valuationDate, $holdingScope);
        $cashValue = $this->resolveCashValue($plan, $valuationDate);
        $liabilities = $this->resolveLiabilities($plan, $valuationDate);
        $outstandingUnits = $this->resolveOutstandingUnits($plan);

        $grossAssetValue = round(
            $equity['total_market_value']
            + $bond['total_market_value']
            + $bond['total_accrued_interest']
            + $cashValue,
            2
        );

        $netAssetValue = round($grossAssetValue - $liabilities, 2);
        $navPerUnit = round($netAssetValue / $outstandingUnits, 6);

        return [
            'plan_id' => $plan->id,
            'valuation_date' => $valuationDate->toDateString(),
            'plan_family' => $plan->configuration?->plan_family,
            'equity_market_value' => round($equity['total_market_value'], 2),
            'bond_market_value' => round($bond['total_market_value'], 2),
            'bond_accrued_interest' => round($bond['total_accrued_interest'], 2),
            'cash_value' => round($cashValue, 2),
            'total_gross_asset_value' => $grossAssetValue,
            'total_liabilities' => round($liabilities, 2),
            'net_asset_value' => $netAssetValue,
            'outstanding_units' => round($outstandingUnits, 6),
            'nav_per_unit' => $navPerUnit,
            'price_source' => 'dse_closing_snapshot',
            'calculation_status' => 'calculated',
            'breakdown' => [
                'equities' => $equity['breakdown'],
                'bonds' => $bond['breakdown'],
                'cash_positions_count' => $cashPositionsCount,
            ],
        ];
    }

    private function resolveEquityValue(Plan $plan, Carbon $valuationDate, string $holdingScope): array
    {
        if (! in_array($holdingScope, ['equity_only', 'equity_and_bond'], true)) {
            return [
                'total_market_value' => 0.0,
                'breakdown' => [],
            ];
        }

        $holdings = $plan->equityHoldings()
            ->where('holding_status', 'active')
            ->with('marketSecurity')
            ->get();

        $breakdown = [];
        $total = 0.0;

        foreach ($holdings as $holding) {
            $security = $holding->marketSecurity;

            if (! $security) {
                continue;
            }

            $snapshot = $this->resolveLatestPriceSnapshot(
                $security->id,
                $valuationDate->toDateString()
            );

            if (! $snapshot) {
                throw ValidationException::withMessages([
                    'market_price' => [
                        "Missing price snapshot for {$security->symbol} on or before {$valuationDate->toDateString()}.",
                    ],
                ]);
            }

            $quantity = (float) $holding->quantity;
            $marketPrice = (float) $snapshot->market_price;
            $marketValue = round($quantity * $marketPrice, 2);

            $total += $marketValue;

            $breakdown[] = [
                'holding_id' => $holding->id,
                'symbol' => $security->symbol,
                'company_name' => $security->company_name,
                'quantity' => $quantity,
                'market_price' => $marketPrice,
                'market_value' => $marketValue,
                'price_snapshot_id' => $snapshot->id,
                'price_date' => $snapshot->price_date?->toDateString(),
            ];
        }

        return [
            'total_market_value' => round($total, 2),
            'breakdown' => $breakdown,
        ];
    }

    private function resolveBondValue(Plan $plan, Carbon $valuationDate, string $holdingScope): array
    {
        if (! in_array($holdingScope, ['bond_only', 'equity_and_bond'], true)) {
            return [
                'total_market_value' => 0.0,
                'total_accrued_interest' => 0.0,
                'breakdown' => [],
            ];
        }

        $holdings = $plan->bondHoldings()
            ->where('holding_status', 'active')
            ->get();

        $breakdown = [];
        $totalMarketValue = 0.0;
        $totalAccruedInterest = 0.0;

        foreach ($holdings as $holding) {
            $marketValue = (float) ($holding->principal_amount ?? 0);
            $accruedInterest = (float) ($holding->accrued_interest_amount ?? 0);

            $totalMarketValue += $marketValue;
            $totalAccruedInterest += $accruedInterest;

            $breakdown[] = [
                'holding_id' => $holding->id,
                'bond_name' => $holding->bond_name,
                'bond_code' => $holding->bond_code,
                'principal_amount' => $marketValue,
                'coupon_rate_percent' => (float) ($holding->coupon_rate_percent ?? 0),
                'accrued_interest_amount' => $accruedInterest,
            ];
        }

        return [
            'total_market_value' => round($totalMarketValue, 2),
            'total_accrued_interest' => round($totalAccruedInterest, 2),
            'breakdown' => $breakdown,
        ];
    }

    private function resolveCashValue(Plan $plan, Carbon $valuationDate): float
    {
        return round(
            (float) $plan->cashPositions()
                ->whereDate('position_date', '<=', $valuationDate->toDateString())
                ->sum('cash_amount'),
            2
        );
    }

    private function resolveLiabilities(Plan $plan, Carbon $valuationDate): float
    {
        return 0.0;
    }

    private function resolveOutstandingUnits(Plan $plan): float
    {
        $unitSummary = $this->planUnitResolverService->getUnitSummary($plan);
        $outstandingUnits = (float) ($unitSummary['outstanding_units'] ?? 0);

        if ($outstandingUnits <= 0) {
            throw ValidationException::withMessages([
                'outstanding_units' => [
                    'Outstanding units must be greater than zero to calculate NAV.',
                ],
            ]);
        }

        return $outstandingUnits;
    }

    private function resolveLatestPriceSnapshot(int $marketSecurityId, string $valuationDate): ?MarketSecurityPriceSnapshot
    {
        $exact = MarketSecurityPriceSnapshot::query()
            ->where('market_security_id', $marketSecurityId)
            ->whereDate('price_date', $valuationDate)
            ->latest('id')
            ->first();

        if ($exact) {
            return $exact;
        }

        return MarketSecurityPriceSnapshot::query()
            ->where('market_security_id', $marketSecurityId)
            ->whereDate('price_date', '<=', $valuationDate)
            ->latest('price_date')
            ->latest('id')
            ->first();
    }
}