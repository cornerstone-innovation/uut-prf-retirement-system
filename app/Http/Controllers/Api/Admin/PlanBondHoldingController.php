<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\PlanBondHolding;
use App\Http\Requests\Plan\StorePlanBondHoldingRequest;

class PlanBondHoldingController extends Controller
{
    public function index(Plan $plan): JsonResponse
    {
        $rows = $plan->bondHoldings()
            ->latest('investment_date')
            ->latest('id')
            ->get();

        return response()->json([
            'message' => 'Plan bond holdings retrieved successfully.',
            'data' => $rows,
        ]);
    }

    public function store(StorePlanBondHoldingRequest $request, Plan $plan): JsonResponse
    {
        $holding = PlanBondHolding::query()->create([
            'uuid' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'bond_name' => $request->input('bond_name'),
            'bond_code' => $request->input('bond_code'),
            'principal_amount' => $request->input('principal_amount'),
            'coupon_rate_percent' => $request->input('coupon_rate_percent'),
            'issue_date' => $request->input('issue_date'),
            'investment_date' => $request->input('investment_date'),
            'maturity_date' => $request->input('maturity_date'),
            'coupon_frequency' => $request->input('coupon_frequency'),
            'last_coupon_date' => $request->input('last_coupon_date'),
            'next_coupon_date' => $request->input('next_coupon_date'),
            'accrued_interest_amount' => $request->input('accrued_interest_amount', 0),
            'face_value' => $request->input('face_value'),
            'holding_status' => $request->input('holding_status', 'active'),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'message' => 'Plan bond holding created successfully.',
            'data' => $holding,
        ], 201);
    }
}