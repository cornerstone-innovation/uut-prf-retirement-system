<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Investor;
use App\Models\Payment;
use App\Models\PurchaseRequest;
use App\Models\UnitLot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Models\InvestmentTransaction;
use App\Application\Services\Nav\NavRecordService;

class AdminInvestorPortfolioController extends Controller
{
    public function __construct(
        private readonly NavRecordService $navRecordService
    ) {
    }



    public function index(Request $request): JsonResponse
{
    $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

    $query = Investor::query()
        ->with([
            'contact',
            'investorCategory',
        ]);

    if ($request->filled('search')) {
        $search = trim((string) $request->string('search'));

        $query->where(function ($inner) use ($search) {
            $inner->where('investor_number', 'ilike', "%{$search}%")
                ->orWhere('full_name', 'ilike', "%{$search}%")
                ->orWhere('company_name', 'ilike', "%{$search}%")
                ->orWhere('first_name', 'ilike', "%{$search}%")
                ->orWhere('middle_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%");
        });
    }

    if ($request->filled('kyc_status')) {
        $query->where('kyc_status', $request->string('kyc_status')->toString());
    }

    if ($request->filled('investor_status')) {
        $query->where('investor_status', $request->string('investor_status')->toString());
    }

    if ($request->filled('investor_type')) {
        $query->where('investor_type', $request->string('investor_type')->toString());
    }

    $investors = $query
        ->latest('id')
        ->paginate($perPage)
        ->withQueryString();

    $rows = collect($investors->items())->map(function (Investor $investor) {
        $lots = UnitLot::query()
            ->with('plan')
            ->where('investor_id', $investor->id)
            ->where('remaining_units', '>', 0)
            ->get();

        $totalUnits = (float) $lots->sum(fn (UnitLot $lot) => (float) $lot->remaining_units);
        $totalInvestedAmount = (float) $lots->sum(fn (UnitLot $lot) => (float) $lot->gross_amount);

        $totalCurrentValue = (float) $lots
            ->groupBy('plan_id')
            ->sum(function (Collection $planLots) {
                $plan = $planLots->first()?->plan;

                if (! $plan) {
                    return 0;
                }

                $latestPublishedNav = $this->navRecordService->getLatestPublishedNav($plan);

                if (! $latestPublishedNav) {
                    return 0;
                }

                $latestNav = (float) $latestPublishedNav->nav_per_unit;
                $remainingUnits = (float) $planLots->sum(
                    fn (UnitLot $lot) => (float) $lot->remaining_units
                );

                return round($remainingUnits * $latestNav, 2);
            });

        $transactionsCount = InvestmentTransaction::query()
            ->where('investor_id', $investor->id)
            ->count();

        return [
            'id' => $investor->id,
            'uuid' => $investor->uuid,
            'investor_number' => $investor->investor_number,
            'full_name' => $investor->full_name,
            'company_name' => $investor->company_name,
            'investor_type' => $investor->investor_type,
            'kyc_status' => $investor->kyc_status,
            'investor_status' => $investor->investor_status,
            'onboarding_status' => $investor->onboarding_status,
            'nationality' => $investor->nationality,
            'contact' => [
                'email' => $investor->contact?->email,
                'phone' => $investor->contact?->phone_number,
            ],
            'category' => [
                'id' => $investor->investorCategory?->id,
                'name' => $investor->investorCategory?->name,
                'code' => $investor->investorCategory?->code,
            ],
            'summary' => [
                'total_units' => number_format($totalUnits, 6, '.', ''),
                'total_invested_amount' => number_format($totalInvestedAmount, 2, '.', ''),
                'total_current_value' => number_format($totalCurrentValue, 2, '.', ''),
                'transactions_count' => $transactionsCount,
            ],
            'created_at' => optional($investor->created_at)?->toDateTimeString(),
        ];
    })->values();

    return response()->json([
        'message' => 'Admin investors retrieved successfully.',
        'data' => $rows,
        'meta' => [
            'current_page' => $investors->currentPage(),
            'last_page' => $investors->lastPage(),
            'per_page' => $investors->perPage(),
            'total' => $investors->total(),
        ],
    ]);
}

    public function summary(Investor $investor): JsonResponse
    {
        $investor->loadMissing([
            'contact',
            'investorCategory',
        ]);

        $holdings = UnitLot::query()
            ->with('plan.category')
            ->where('investor_id', $investor->id)
            ->where('remaining_units', '>', 0)
            ->get();

        $holdingsByPlan = $this->buildHoldingsByPlan($holdings);

        $totalUnitsHeld = (float) $holdings->sum(fn (UnitLot $lot) => (float) $lot->remaining_units);
        $totalInvestedAmount = (float) $holdings->sum(fn (UnitLot $lot) => (float) $lot->gross_amount);
        $totalCurrentValue = (float) collect($holdingsByPlan)
            ->filter(fn (array $row) => $row['current_value'] !== null)
            ->sum(fn (array $row) => (float) $row['current_value']);

        $transactions = InvestmentTransaction::query()
            ->where('investor_id', $investor->id)
            ->get();

        $purchaseRequests = PurchaseRequest::query()
            ->where('investor_id', $investor->id)
            ->get();

        $payments = Payment::query()
            ->where('investor_id', $investor->id)
            ->get();

        return response()->json([
            'message' => 'Admin investor portfolio summary retrieved successfully.',
            'data' => [
                'investor' => [
                    'id' => $investor->id,
                    'uuid' => $investor->uuid,
                    'investor_number' => $investor->investor_number,
                    'full_name' => $investor->full_name,
                    'first_name' => $investor->first_name,
                    'middle_name' => $investor->middle_name,
                    'last_name' => $investor->last_name,
                    'company_name' => $investor->company_name,
                    'investor_type' => $investor->investor_type,
                    'nationality' => $investor->nationality,
                    'investor_status' => $investor->investor_status,
                    'onboarding_status' => $investor->onboarding_status,
                    'kyc_status' => $investor->kyc_status,
                    'risk_profile' => $investor->risk_profile,
                    'category' => [
                        'id' => $investor->investorCategory?->id,
                        'name' => $investor->investorCategory?->name,
                        'code' => $investor->investorCategory?->code,
                    ],
                    'contact' => [
                        'email' => $investor->contact?->email,
                        'phone' => $investor->contact?->phone_number,
                    ],
                    'created_at' => optional($investor->created_at)?->toDateTimeString(),
                ],
                'summary' => [
                    'plans_count' => count($holdingsByPlan),
                    'total_units_held' => number_format($totalUnitsHeld, 6, '.', ''),
                    'total_invested_amount' => number_format($totalInvestedAmount, 2, '.', ''),
                    'total_current_value' => number_format($totalCurrentValue, 2, '.', ''),
                    'purchase_requests_count' => $purchaseRequests->count(),
                    'payments_count' => $payments->count(),
                    'transactions_count' => $transactions->count(),
                    'successful_payments_count' => $payments->where('status', 'paid')->count(),
                    'completed_transactions_count' => $transactions->where('status', 'completed')->count(),
                ],
                'transaction_totals' => [
                    'gross_amount_total' => number_format(
                        (float) $transactions->sum(fn (InvestmentTransaction $tx) => (float) $tx->gross_amount),
                        2,
                        '.',
                        ''
                    ),
                    'net_amount_total' => number_format(
                        (float) $transactions->sum(fn (InvestmentTransaction $tx) => (float) $tx->net_amount),
                        2,
                        '.',
                        ''
                    ),
                    'units_total' => number_format(
                        (float) $transactions->sum(fn (InvestmentTransaction $tx) => (float) $tx->units),
                        6,
                        '.',
                        ''
                    ),
                ],
                'holdings_by_plan' => $holdingsByPlan,
                'status_breakdown' => [
                    'purchase_requests' => $purchaseRequests
                        ->groupBy('status')
                        ->map(fn (Collection $rows) => $rows->count())
                        ->toArray(),
                    'payments' => $payments
                        ->groupBy('status')
                        ->map(fn (Collection $rows) => $rows->count())
                        ->toArray(),
                    'transactions' => $transactions
                        ->groupBy('status')
                        ->map(fn (Collection $rows) => $rows->count())
                        ->toArray(),
                ],
            ],
        ]);
    }

    public function holdings(Request $request, Investor $investor): JsonResponse
    {
        $lots = UnitLot::query()
            ->with([
                'plan.category',
                'transaction',
            ])
            ->where('investor_id', $investor->id)
            ->when(
                $request->boolean('active_only', true),
                fn ($query) => $query->where('remaining_units', '>', 0)
            )
            ->when(
                $request->filled('plan_id'),
                fn ($query) => $query->where('plan_id', $request->integer('plan_id'))
            )
            ->orderByDesc('id')
            ->get();

        $rows = $lots->map(function (UnitLot $lot) {
            $latestPublishedNav = $lot->plan
                ? $this->navRecordService->getLatestPublishedNav($lot->plan)
                : null;

            $remainingUnits = (float) $lot->remaining_units;
            $latestNav = $latestPublishedNav ? (float) $latestPublishedNav->nav_per_unit : null;
            $currentValue = $latestNav !== null ? round($remainingUnits * $latestNav, 2) : null;

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
                'transaction' => [
                    'id' => $lot->transaction?->id,
                    'uuid' => $lot->transaction?->uuid,
                    'transaction_type' => $lot->transaction?->transaction_type,
                    'status' => $lot->transaction?->status,
                    'trade_date' => optional($lot->transaction?->trade_date)?->toDateString(),
                    'pricing_date' => optional($lot->transaction?->pricing_date)?->toDateString(),
                ],
                'original_units' => number_format((float) $lot->original_units, 6, '.', ''),
                'remaining_units' => number_format((float) $lot->remaining_units, 6, '.', ''),
                'purchase_nav_per_unit' => number_format((float) $lot->nav_per_unit, 6, '.', ''),
                'latest_published_nav' => $latestNav !== null ? number_format($latestNav, 6, '.', '') : null,
                'latest_nav_date' => $latestPublishedNav?->valuation_date?->toDateString(),
                'gross_amount' => number_format((float) $lot->gross_amount, 2, '.', ''),
                'current_value' => $currentValue !== null ? number_format($currentValue, 2, '.', '') : null,
                'acquired_date' => optional($lot->acquired_date)?->toDateString(),
                'status' => $lot->status,
                'metadata' => $lot->metadata,
                'created_at' => optional($lot->created_at)?->toDateTimeString(),
            ];
        })->values();

        return response()->json([
            'message' => 'Admin investor holdings retrieved successfully.',
            'data' => $rows,
        ]);
    }

    public function transactions(Request $request, Investor $investor): JsonResponse
    {
        $query = InvestmentTransaction::query()
            ->with('plan.category', 'purchaseRequest')
            ->where('investor_id', $investor->id);

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->string('transaction_type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->integer('plan_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('trade_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('trade_date', '<=', $request->string('date_to')->toString());
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $transactions = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Admin investor transactions retrieved successfully.',
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
                    'purchase_request' => [
                        'id' => $transaction->purchaseRequest?->id,
                        'uuid' => $transaction->purchaseRequest?->uuid,
                        'status' => $transaction->purchaseRequest?->status,
                    ],
                    'gross_amount' => number_format((float) $transaction->gross_amount, 2, '.', ''),
                    'net_amount' => number_format((float) $transaction->net_amount, 2, '.', ''),
                    'units' => number_format((float) $transaction->units, 6, '.', ''),
                    'nav_per_unit' => number_format((float) $transaction->nav_per_unit, 6, '.', ''),
                    'currency' => $transaction->currency,
                    'option' => $transaction->option,
                    'trade_date' => optional($transaction->trade_date)?->toDateString(),
                    'pricing_date' => optional($transaction->pricing_date)?->toDateString(),
                    'processed_at' => optional($transaction->processed_at)?->toDateTimeString(),
                    'metadata' => $transaction->metadata,
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

    public function purchaseRequests(Request $request, Investor $investor): JsonResponse
    {
        $query = PurchaseRequest::query()
            ->with([
                'plan.category',
                'latestPayment',
                'investmentTransaction',
            ])
            ->where('investor_id', $investor->id);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->integer('plan_id'));
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $rows = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Admin investor purchase requests retrieved successfully.',
            'data' => collect($rows->items())->map(function (PurchaseRequest $requestRow) {
                return [
                    'id' => $requestRow->id,
                    'uuid' => $requestRow->uuid,
                    'plan' => [
                        'id' => $requestRow->plan?->id,
                        'code' => $requestRow->plan?->code,
                        'name' => $requestRow->plan?->name,
                        'category' => [
                            'id' => $requestRow->plan?->category?->id,
                            'code' => $requestRow->plan?->category?->code,
                            'name' => $requestRow->plan?->category?->name,
                        ],
                    ],
                    'amount' => number_format((float) $requestRow->amount, 2, '.', ''),
                    'currency' => $requestRow->currency,
                    'request_type' => $requestRow->request_type,
                    'option' => $requestRow->option,
                    'status' => $requestRow->status,
                    'pricing_date' => optional($requestRow->pricing_date)?->toDateString(),
                    'submitted_at' => optional($requestRow->submitted_at)?->toDateTimeString(),
                    'latest_payment' => $requestRow->latestPayment ? [
                        'id' => $requestRow->latestPayment->id,
                        'reference' => $requestRow->latestPayment->reference,
                        'amount' => number_format((float) $requestRow->latestPayment->amount, 2, '.', ''),
                        'status' => $requestRow->latestPayment->status,
                        'paid_at' => optional($requestRow->latestPayment->paid_at)?->toDateTimeString(),
                    ] : null,
                    'investment_transaction' => $requestRow->investmentTransaction ? [
                        'id' => $requestRow->investmentTransaction->id,
                        'uuid' => $requestRow->investmentTransaction->uuid,
                        'status' => $requestRow->investmentTransaction->status,
                        'units' => number_format((float) $requestRow->investmentTransaction->units, 6, '.', ''),
                    ] : null,
                    'metadata' => $requestRow->metadata,
                    'notes' => $requestRow->notes,
                    'created_at' => optional($requestRow->created_at)?->toDateTimeString(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function payments(Request $request, Investor $investor): JsonResponse
    {
        $query = Payment::query()
            ->with([
                'purchaseRequest.plan.category',
            ])
            ->where('investor_id', $investor->id);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->string('provider')->toString());
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $rows = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Admin investor payments retrieved successfully.',
            'data' => collect($rows->items())->map(function (Payment $payment) {
                return [
                    'id' => $payment->id,
                    'uuid' => $payment->uuid,
                    'reference' => $payment->reference,
                    'provider_reference' => $payment->provider_reference,
                    'provider' => $payment->provider,
                    'amount' => number_format((float) $payment->amount, 2, '.', ''),
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'paid_at' => optional($payment->paid_at)?->toDateTimeString(),
                    'cancelled_at' => optional($payment->cancelled_at)?->toDateTimeString(),
                    'purchase_request' => [
                        'id' => $payment->purchaseRequest?->id,
                        'uuid' => $payment->purchaseRequest?->uuid,
                        'status' => $payment->purchaseRequest?->status,
                        'plan' => [
                            'id' => $payment->purchaseRequest?->plan?->id,
                            'code' => $payment->purchaseRequest?->plan?->code,
                            'name' => $payment->purchaseRequest?->plan?->name,
                            'category' => [
                                'id' => $payment->purchaseRequest?->plan?->category?->id,
                                'code' => $payment->purchaseRequest?->plan?->category?->code,
                                'name' => $payment->purchaseRequest?->plan?->category?->name,
                            ],
                        ],
                    ],
                    'metadata' => $payment->metadata,
                    'created_at' => optional($payment->created_at)?->toDateTimeString(),
                ];
            })->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function activity(Request $request, Investor $investor): JsonResponse
    {
        $purchaseRequests = PurchaseRequest::query()
            ->with('plan')
            ->where('investor_id', $investor->id)
            ->get()
            ->map(function (PurchaseRequest $row) {
                return [
                    'type' => 'purchase_request',
                    'id' => $row->id,
                    'uuid' => $row->uuid,
                    'status' => $row->status,
                    'title' => 'Purchase Request Created',
                    'description' => "Purchase request for {$row->plan?->name}",
                    'amount' => number_format((float) $row->amount, 2, '.', ''),
                    'units' => null,
                    'plan' => [
                        'id' => $row->plan?->id,
                        'code' => $row->plan?->code,
                        'name' => $row->plan?->name,
                    ],
                    'occurred_at' => optional($row->submitted_at ?? $row->created_at)?->toDateTimeString(),
                    'sort_at' => $row->submitted_at ?? $row->created_at,
                    'data' => [
                        'request_type' => $row->request_type,
                        'option' => $row->option,
                        'pricing_date' => optional($row->pricing_date)?->toDateString(),
                    ],
                ];
            });

        $payments = Payment::query()
            ->with('purchaseRequest.plan')
            ->where('investor_id', $investor->id)
            ->get()
            ->map(function (Payment $row) {
                return [
                    'type' => 'payment',
                    'id' => $row->id,
                    'uuid' => $row->uuid,
                    'status' => $row->status,
                    'title' => 'Payment Event',
                    'description' => "Payment {$row->status}",
                    'amount' => number_format((float) $row->amount, 2, '.', ''),
                    'units' => null,
                    'plan' => [
                        'id' => $row->purchaseRequest?->plan?->id,
                        'code' => $row->purchaseRequest?->plan?->code,
                        'name' => $row->purchaseRequest?->plan?->name,
                    ],
                    'occurred_at' => optional($row->paid_at ?? $row->created_at)?->toDateTimeString(),
                    'sort_at' => $row->paid_at ?? $row->created_at,
                    'data' => [
                        'reference' => $row->reference,
                        'provider_reference' => $row->provider_reference,
                        'payment_method' => $row->payment_method,
                        'provider' => $row->provider,
                    ],
                ];
            });

        $transactions = InvestmentTransaction::query()
            ->with('plan')
            ->where('investor_id', $investor->id)
            ->get()
            ->map(function (InvestmentTransaction $row) {
                return [
                    'type' => 'investment_transaction',
                    'id' => $row->id,
                    'uuid' => $row->uuid,
                    'status' => $row->status,
                    'title' => 'Investment Transaction',
                    'description' => ucfirst($row->transaction_type) . ' transaction completed',
                    'amount' => number_format((float) $row->gross_amount, 2, '.', ''),
                    'units' => number_format((float) $row->units, 6, '.', ''),
                    'plan' => [
                        'id' => $row->plan?->id,
                        'code' => $row->plan?->code,
                        'name' => $row->plan?->name,
                    ],
                    'occurred_at' => optional($row->processed_at ?? $row->created_at)?->toDateTimeString(),
                    'sort_at' => $row->processed_at ?? $row->created_at,
                    'data' => [
                        'transaction_type' => $row->transaction_type,
                        'nav_per_unit' => number_format((float) $row->nav_per_unit, 6, '.', ''),
                        'pricing_date' => optional($row->pricing_date)?->toDateString(),
                    ],
                ];
            });

        $unitLots = UnitLot::query()
            ->with('plan')
            ->where('investor_id', $investor->id)
            ->get()
            ->map(function (UnitLot $row) {
                return [
                    'type' => 'unit_lot',
                    'id' => $row->id,
                    'uuid' => $row->uuid,
                    'status' => $row->status,
                    'title' => 'Units Allocated',
                    'description' => 'Unit lot created',
                    'amount' => number_format((float) $row->gross_amount, 2, '.', ''),
                    'units' => number_format((float) $row->original_units, 6, '.', ''),
                    'plan' => [
                        'id' => $row->plan?->id,
                        'code' => $row->plan?->code,
                        'name' => $row->plan?->name,
                    ],
                    'occurred_at' => optional($row->created_at)?->toDateTimeString(),
                    'sort_at' => $row->created_at,
                    'data' => [
                        'remaining_units' => number_format((float) $row->remaining_units, 6, '.', ''),
                        'acquired_date' => optional($row->acquired_date)?->toDateString(),
                        'nav_per_unit' => number_format((float) $row->nav_per_unit, 6, '.', ''),
                    ],
                ];
            });

        $activity = collect()
            ->merge($purchaseRequests)
            ->merge($payments)
            ->merge($transactions)
            ->merge($unitLots)
            ->sortByDesc(fn (array $item) => $item['sort_at'])
            ->values();

        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $paginated = $this->paginateCollection($activity, $perPage, $page);

        return response()->json([
            'message' => 'Admin investor activity retrieved successfully.',
            'data' => collect($paginated->items())->map(function (array $item) {
                unset($item['sort_at']);
                return $item;
            })->values(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    private function buildHoldingsByPlan(Collection $holdings): array
    {
        return $holdings
            ->groupBy('plan_id')
            ->map(function (Collection $lots, $planId) {
                /** @var UnitLot|null $firstLot */
                $firstLot = $lots->first();
                $plan = $firstLot?->plan;

                $latestPublishedNav = $plan
                    ? $this->navRecordService->getLatestPublishedNav($plan)
                    : null;

                $totalOriginalUnits = (float) $lots->sum(fn (UnitLot $lot) => (float) $lot->original_units);
                $totalRemainingUnits = (float) $lots->sum(fn (UnitLot $lot) => (float) $lot->remaining_units);
                $totalInvestedAmount = (float) $lots->sum(fn (UnitLot $lot) => (float) $lot->gross_amount);
                $latestNav = $latestPublishedNav ? (float) $latestPublishedNav->nav_per_unit : null;
                $currentValue = $latestNav !== null ? round($totalRemainingUnits * $latestNav, 2) : null;

                return [
                    'plan_id' => (int) $planId,
                    'plan' => [
                        'id' => $plan?->id,
                        'code' => $plan?->code,
                        'name' => $plan?->name,
                        'category' => [
                            'id' => $plan?->category?->id,
                            'code' => $plan?->category?->code,
                            'name' => $plan?->category?->name,
                        ],
                    ],
                    'latest_published_nav' => $latestNav !== null ? number_format($latestNav, 6, '.', '') : null,
                    'latest_nav_date' => $latestPublishedNav?->valuation_date?->toDateString(),
                    'total_original_units' => number_format($totalOriginalUnits, 6, '.', ''),
                    'total_remaining_units' => number_format($totalRemainingUnits, 6, '.', ''),
                    'total_invested_amount' => number_format($totalInvestedAmount, 2, '.', ''),
                    'current_value' => $currentValue !== null ? number_format($currentValue, 2, '.', '') : null,
                ];
            })
            ->values()
            ->all();
    }

    private function paginateCollection(Collection $items, int $perPage, int $page): LengthAwarePaginator
    {
        $total = $items->count();
        $results = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}