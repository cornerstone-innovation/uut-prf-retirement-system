<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class PlanConfigurationController extends Controller
{
    public function show(Plan $plan): JsonResponse
    {
        return response()->json([
            'message' => 'Plan configuration retrieved successfully.',
            'data' => $plan->configuration,
        ]);
    }

    public function store(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'plan_family' => ['required', 'string'],
            'valuation_method' => ['required', 'string'],
            'phase_status' => ['required', 'string'],
            'initial_offer_start_date' => ['nullable', 'date'],
            'initial_offer_end_date' => ['nullable', 'date'],
            'initial_offer_price' => ['nullable', 'numeric'],
            'total_units_on_offer' => ['nullable', 'numeric'],
            'market_close_time' => ['required'],
            'market_close_timezone' => ['required', 'string'],
            'auto_calculate_nav' => ['required', 'boolean'],
            'allow_nav_override' => ['required', 'boolean'],
            'allow_phase_override' => ['required', 'boolean'],
            'allow_post_offer_sales' => ['required', 'boolean'],
            'unit_sale_cap_type' => ['required', 'string'],
        ]);

        $configuration = $plan->configuration()->create([
            ...$validated,
            'uuid' => (string) Str::uuid(),
        ]);

        return response()->json([
            'message' => 'Plan configuration created successfully.',
            'data' => $configuration,
        ], 201);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $validated = $request->validate([
            'plan_family' => ['required', 'string'],
            'valuation_method' => ['required', 'string'],
            'phase_status' => ['required', 'string'],
            'initial_offer_start_date' => ['nullable', 'date'],
            'initial_offer_end_date' => ['nullable', 'date'],
            'initial_offer_price' => ['nullable', 'numeric'],
            'total_units_on_offer' => ['nullable', 'numeric'],
            'market_close_time' => ['required'],
            'market_close_timezone' => ['required', 'string'],
            'auto_calculate_nav' => ['required', 'boolean'],
            'allow_nav_override' => ['required', 'boolean'],
            'allow_phase_override' => ['required', 'boolean'],
            'allow_post_offer_sales' => ['required', 'boolean'],
            'unit_sale_cap_type' => ['required', 'string'],
        ]);

        $configuration = $plan->configuration()->updateOrCreate(
            ['plan_id' => $plan->id],
            [
                ...$validated,
                'uuid' => optional($plan->configuration)->uuid ?: (string) Str::uuid(),
            ]
        );

        return response()->json([
            'message' => 'Plan configuration updated successfully.',
            'data' => $configuration,
        ]);
    }
}