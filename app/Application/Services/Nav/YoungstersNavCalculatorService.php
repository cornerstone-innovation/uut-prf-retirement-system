<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use App\Models\MarketSecurityPriceSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class YoungstersNavCalculatorService
{
    public function calculate(Plan $plan, string $valuationDate): array
    {
        $date = Carbon::parse($valuationDate)->toDateString();

        $equityHoldings = $plan->equityHoldings()->with('marketSecurity')->get();
        $cashPositions = $plan->cashPositions()
            ->whereDate('position_date', '<=', $date)
            ->get();

        $equityBreakdown = [];
        $totalEquityMarketValue = 0.0;

        foreach ($equityHoldings as $holding) {
            $security = $holding->marketSecurity;

            if (! $security) {
                continue;
            }

            $priceSnapshot = MarketSecurityPriceSnapshot::query()
                ->where('market_security_id', $security->id)
                ->whereDate('price_date', $date)
                ->latest('id')
                ->first();

            if (! $priceSnapshot) {
                throw ValidationException::withMessages([
                    'valuation_date' => [
                        "Missing price snapshot for security {$security->symbol} on {$date}.",
                    ],
                ]);
            }

            $marketPrice = (float) $priceSnapshot->market_price;
            $quantity = (float) $holding->quantity;
            $marketValue = $quantity * $marketPrice;

            $totalEquityMarketValue += $marketValue;

            $equityBreakdown[] = [
                'holding_id' => $holding->id,
                'symbol' => $security->symbol,
                'company_name' => $security->company_name,
                'quantity' => $quantity,
                'market_price' => $marketPrice,
                'market_value' => round($marketValue, 2),
            ];
        }

        $cashValue = (float) $cashPositions->sum('cash_amount');

        $outstandingUnits = (float) $this->resolveOutstandingUnits($plan);

        if ($outstandingUnits <= 0) {
            throw ValidationException::withMessages([
                'units' => ['Outstanding units must be greater than zero before NAV can be calculated.'],
            ]);
        }

        $grossAssetValue = $totalEquityMarketValue + $cashValue;
        $liabilities = 0.0;
        $netAssetValue = $grossAssetValue - $liabilities;
        $navPerUnit = $netAssetValue / $outstandingUnits;

        return [
            'plan_family' => 'youngsters',
            'valuation_date' => $date,
            'equity_market_value' => round($totalEquityMarketValue, 2),
            'bond_market_value' => 0,
            'bond_accrued_interest' => 0,
            'cash_value' => round($cashValue, 2),
            'total_gross_asset_value' => round($grossAssetValue, 2),
            'total_liabilities' => round($liabilities, 2),
            'net_asset_value' => round($netAssetValue, 2),
            'outstanding_units' => round($outstandingUnits, 6),
            'nav_per_unit' => round($navPerUnit, 6),
            'price_source' => 'dse_closing_snapshot',
            'calculation_status' => 'calculated',
            'breakdown' => [
                'equities' => $equityBreakdown,
                'cash_positions_count' => $cashPositions->count(),
            ],
        ];
    }

    protected function resolveOutstandingUnits(Plan $plan): float
    {
        return (float) $plan->transactions()
            ->where('status', 'completed')
            ->sum('units');
    }
} 