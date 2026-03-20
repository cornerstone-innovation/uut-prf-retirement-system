<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Application\Services\Plan\PlanEligibilityService;
use App\Application\Services\Purchase\PurchasePreviewService;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Requests\Plan\CheckPurchaseEligibilityRequest;
use App\Http\Requests\Plan\PurchasePreviewRequest;
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
{
    public function index(Request $request, PlanEligibilityService $eligibilityService): JsonResponse
    {
        $user = $request->user();
        $investor = $user?->investor;

        $query = Plan::query()
            ->with(['category', 'activeRule']);

        if ($investor) {
            $eligibilityService->ensureCanViewProducts($investor);

            $query->whereIn('status', ['approved', 'active']);
        }

        if ($request->filled('category_code')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('code', $request->string('category_code'));
            });
        }

        return response()->json([
            'message' => 'Plans retrieved successfully.',
            'data' => PlanResource::collection($query->orderBy('id')->get()),
        ]);
    }

    public function show(Request $request, Plan $plan, PlanEligibilityService $eligibilityService): JsonResponse
    {
        $investor = $request->user()?->investor;

        if ($investor) {
            $eligibilityService->ensureCanViewProducts($investor);
        }

        $plan->load(['category', 'activeRule']);

        return response()->json([
            'message' => 'Plan retrieved successfully.',
            'data' => new PlanResource($plan),
        ]);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('super-admin') || $user->can('manage plans'),
            403
        );

        $plan = Plan::create($request->validated());

        $plan->load(['category', 'activeRule']);

        return response()->json([
            'message' => 'Plan created successfully.',
            'data' => new PlanResource($plan),
        ], 201);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('super-admin') || $user->can('manage plans'),
            403
        );

        $plan->update($request->validated());

        $plan->load(['category', 'activeRule']);

        return response()->json([
            'message' => 'Plan updated successfully.',
            'data' => new PlanResource($plan),
        ]);
    }

    public function purchaseEligibility(
        CheckPurchaseEligibilityRequest $request,
        Plan $plan,
        PlanEligibilityService $eligibilityService
    ): JsonResponse {
        $investor = $request->user()?->investor;

        if (! $investor) {
            throw ValidationException::withMessages([
                'investor' => ['Authenticated user is not linked to an investor profile.'],
            ]);
        }

        $plan->load('activeRule');

        $result = $eligibilityService->ensureCanPurchase(
            investor: $investor,
            plan: $plan,
            amount: (float) $request->input('amount'),
            isAdditionalInvestment: (bool) $request->boolean('is_additional_investment', false),
            isSip: (bool) $request->boolean('is_sip', false),
        );

        return response()->json([
            'message' => 'Purchase eligibility checked successfully.',
            'data' => $result,
        ]);
    }

    public function purchasePreview(
        PurchasePreviewRequest $request,
        Plan $plan,
        PurchasePreviewService $previewService
    ): JsonResponse {
        $investor = $request->user()?->investor;

        if (! $investor) {
            throw ValidationException::withMessages([
                'investor' => ['Authenticated user is not linked to an investor profile.'],
            ]);
        }

        $preview = $previewService->preview(
            investor: $investor,
            plan: $plan,
            amount: (float) $request->input('amount'),
            option: $request->input('option'),
            requestType: $request->input('request_type', 'initial'),
            isSip: (bool) $request->boolean('is_sip', false),
        );

        return response()->json([
            'message' => 'Purchase preview generated successfully.',
            'data' => $preview,
        ]);
    }
}