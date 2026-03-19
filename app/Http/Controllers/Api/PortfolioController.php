<?php

namespace App\Http\Controllers\Api;

use App\Models\UnitLot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\InvestmentTransaction;
use App\Application\Services\Nav\NavRecordService;

class PortfolioController extends Controller
{
    public function __construct(
        private readonly NavRecordService $navRecordService
    ) {
    }

    public function summary(Request $request): JsonResponse
    {
        $investor = $request->user()?->investor;

        if (! $investor) {
            return response()->json([
                'message' => 'Authenticated user is not linked to an investor profile.',
            ], 422);
        }

        $holdings = UnitLot::query()
            ->with('plan')
            ->where('investor_id', $investor->id)
            ->where('remaining_units', '>', 0)
            ->get();

        $grouped = $holdings
            ->groupBy('plan_id')
            ->map(function ($lots, $planId) {
                $firstLot = $lots->first();
                $plan = $firstLot?->plan;

                $latestPublishedNav = $plan
                    ? $this->navRecordService->getLatestPublishedNav($plan)
                    : null;

                $totalOriginalUnits = (float) $lots->sum(fn ($lot) => (float) $lot->original_units);
                $totalRemainingUnits = (float) $lots->sum(fn ($lot) => (float) $lot->remaining_units);
                $totalInvestedAmount = (float) $lots->sum(fn ($lot) => (float) $lot->gross_amount);
                $latestNav = $latestPublishedNav ? (float) $latestPublishedNav->nav_per_unit : null;
                $currentValue = $latestNav ? round($totalRemainingUnits * $latestNav, 2) : null;

                return [
                    'plan_id' => (int) $planId,
                    'plan' => [
                        'id' => $plan?->id,
                        'code' => $plan?->code,
                        'name' => $plan?->name,
                    ],
                    'latest_published_nav' => $latestNav !== null ? number_format($latestNav, 6, '.', '') : null,
                    'latest_nav_date' => $latestPublishedNav?->valuation_date?->toDateString(),
                    'total_original_units' => number_format($totalOriginalUnits, 6, '.', ''),
                    'total_remaining_units' => number_format($totalRemainingUnits, 6, '.', ''),
                    'total_invested_amount' => number_format($totalInvestedAmount, 2, '.', ''),
                    'current_value' => $currentValue !== null ? number_format($currentValue, 2, '.', '') : null,
                ];
            })
            ->values();

        $totalUnits = (float) $holdings->sum(fn ($lot) => (float) $lot->remaining_units);
        $totalInvestedAmount = (float) $holdings->sum(fn ($lot) => (float) $lot->gross_amount);
        $totalCurrentValue = (float) $grouped
            ->filter(fn ($row) => $row['current_value'] !== null)
            ->sum(fn ($row) => (float) $row['current_value']);

        $transactionsCount = InvestmentTransaction::query()
            ->where('investor_id', $investor->id)
            ->count();

        return response()->json([
            'message' => 'Portfolio summary retrieved successfully.',
            'data' => [
                'investor_id' => $investor->id,
                'investor_number' => $investor->investor_number,
                'full_name' => $investor->full_name,
                'summary' => [
                    'plans_count' => $grouped->count(),
                    'total_units' => number_format($totalUnits, 6, '.', ''),
                    'total_invested_amount' => number_format($totalInvestedAmount, 2, '.', ''),
                    'total_current_value' => number_format($totalCurrentValue, 2, '.', ''),
                    'transactions_count' => $transactionsCount,
                ],
                'holdings_by_plan' => $grouped,
            ],
        ]);
    }

    public function holdings(Request $request): JsonResponse
    {
        $investor = $request->user()?->investor;

        if (! $investor) {
            return response()->json([
                'message' => 'Authenticated user is not linked to an investor profile.',
            ], 422);
        }

        $holdings = UnitLot::query()
            ->with('plan.category')
            ->where('investor_id', $investor->id)
            ->where('remaining_units', '>', 0)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'message' => 'Portfolio holdings retrieved successfully.',
            'data' => $holdings->map(function (UnitLot $lot) {
                $latestPublishedNav = $lot->plan
                    ? $this->navRecordService->getLatestPublishedNav($lot->plan)
                    : null;

                $remainingUnits = (float) $lot->remaining_units;
                $latestNav = $latestPublishedNav ? (float) $latestPublishedNav->nav_per_unit : null;
                $currentValue = $latestNav ? round($remainingUnits * $latestNav, 2) : null;

                return [
                    'lot_id' => $lot->id,
                    'lot_uuid' => $lot->uuid,
                    'plan' => [
                        'id' => $lot->plan?->id,
                        'code' => $lot->plan?->code,
                        'name' => $lot->plan?->name,
                        'category' => [
                            'id' => $lot->plan?->category?->id,
                            'code' => $lot->plan?->category?->code,
                            'name' => $lot->plan?->category?->name,
                        ],
                    ],
                    'original_units' => $lot->original_units,
                    'remaining_units' => $lot->remaining_units,
                    'purchase_nav_per_unit' => $lot->nav_per_unit,
                    'latest_published_nav' => $latestNav !== null ? number_format($latestNav, 6, '.', '') : null,
                    'latest_nav_date' => $latestPublishedNav?->valuation_date?->toDateString(),
                    'gross_amount' => $lot->gross_amount,
                    'current_value' => $currentValue !== null ? number_format($currentValue, 2, '.', '') : null,
                    'acquired_date' => optional($lot->acquired_date)?->toDateString(),
                    'status' => $lot->status,
                    'created_at' => optional($lot->created_at)?->toDateTimeString(),
                ];
            })->values(),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $investor = $request->user()?->investor;

        if (! $investor) {
            return response()->json([
                'message' => 'Authenticated user is not linked to an investor profile.',
            ], 422);
        }

        $query = InvestmentTransaction::query()
            ->with('plan.category')
            ->where('investor_id', $investor->id);

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->string('transaction_type')->toString());
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->integer('plan_id'));
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $transactions = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Portfolio transactions retrieved successfully.',
            'data' => collect($transactions->items())->map(function (InvestmentTransaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'uuid' => $transaction->uuid,
                    'transaction_type' => $transaction->transaction_type,
                    'status' => $transaction->status,
                    'plan' => [
                        'id' => $transaction->plan?->id,
                        'code' => $transaction->plan?->code,
                        'name' => $transaction->plan?->name,
                        'category' => [
                            'id' => $transaction->plan?->category?->id,
                            'code' => $transaction->plan?->category?->code,
                            'name' => $transaction->plan?->category?->name,
                        ],
                    ],
                    'gross_amount' => $transaction->gross_amount,
                    'net_amount' => $transaction->net_amount,
                    'units' => $transaction->units,
                    'nav_per_unit' => $transaction->nav_per_unit,
                    'currency' => $transaction->currency,
                    'option' => $transaction->option,
                    'trade_date' => optional($transaction->trade_date)?->toDateString(),
                    'pricing_date' => optional($transaction->pricing_date)?->toDateString(),
                    'processed_at' => optional($transaction->processed_at)?->toDateTimeString(),
                    'created_at' => optional($transaction->created_at)?->toDateTimeString(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}