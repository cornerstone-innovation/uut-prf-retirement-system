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

        'market_close_time' => ['required', 'date_format:H:i'],
        'market_close_timezone' => ['required', 'string'],

        'auto_calculate_nav' => ['required', 'boolean'],
        'allow_nav_override' => ['required', 'boolean'],
        'allow_phase_override' => ['required', 'boolean'],
        'allow_post_offer_sales' => ['required', 'boolean'],
        'holding_scope' => ['required', 'in:equity_only,bond_only,equity_and_bond'],

        'unit_sale_cap_type' => ['required', 'string'],
    ]);

    // normalize time
    $validated['market_close_time'] = \Carbon\Carbon::parse(
        $validated['market_close_time']
    )->format('H:i:s');

    // prevent duplicate config
    if ($plan->configuration) {
        return response()->json([
            'message' => 'Configuration already exists. Use update instead.',
            'data' => $plan->configuration,
        ], 422);
    }

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
        'plan_family' => ['sometimes', 'string'],
        'valuation_method' => ['sometimes', 'string'],
        'phase_status' => ['sometimes', 'string'],
        'initial_offer_start_date' => ['sometimes', 'nullable', 'date'],
        'initial_offer_end_date' => ['sometimes', 'nullable', 'date'],
        'initial_offer_price' => ['sometimes', 'nullable', 'numeric'],
        'total_units_on_offer' => ['sometimes', 'nullable', 'numeric'],
        'market_close_time' => ['sometimes', 'date_format:H:i'],
        'market_close_timezone' => ['sometimes', 'string'],
        'auto_calculate_nav' => ['sometimes', 'boolean'],
        'allow_nav_override' => ['sometimes', 'boolean'],
        'allow_phase_override' => ['sometimes', 'boolean'],
        'allow_post_offer_sales' => ['sometimes', 'boolean'],
        'unit_sale_cap_type' => ['sometimes', 'string'],
        'holding_scope' => ['sometimes', 'in:equity_only,bond_only,equity_and_bond'],
    ]);

    $configuration = $plan->configuration;

    if (! $configuration) {
        // create if missing
        $configuration = $plan->configuration()->create([
            ...$validated,
            'uuid' => (string) Str::uuid(),
        ]);
    } else {
        // normalize time if provided
        if (isset($validated['market_close_time'])) {
            $validated['market_close_time'] = \Carbon\Carbon::parse(
                $validated['market_close_time']
            )->format('H:i:s');
        }

        $configuration->fill($validated);
        $configuration->save();
    }

    return response()->json([
        'message' => 'Plan configuration updated successfully.',
        'data' => $configuration->fresh(),
    ]);
}
}