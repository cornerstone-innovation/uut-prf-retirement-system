<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use App\Models\PlanRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use Illuminate\Validation\ValidationException;
use App\Application\Services\Plan\PlanRuleService;
use App\Application\Services\Plan\PlanEligibilityService;
use App\Application\Services\Purchase\PurchasePreviewService;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Requests\Plan\StorePlanRuleRequest;
use App\Http\Requests\Plan\UpdatePlanRuleRequest;
use App\Http\Requests\Plan\CheckPurchaseEligibilityRequest;
use App\Http\Requests\Plan\PurchasePreviewRequest;

class PlanController extends Controller
{
    public function index(Request $request, PlanEligibilityService $eligibilityService): JsonResponse
    {
        $user = $request->user();
        $investor = $user?->investor;

        $query = Plan::query()
            ->with(['fund', 'category', 'activeRule']);

        if ($investor) {
            $eligibilityService->ensureCanViewProducts($investor);
            $query->whereIn('status', ['approved', 'active']);
        }

        if ($request->filled('category_code')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('code', $request->string('category_code')->toString());
            });
        }

        if ($request->filled('fund_id')) {
            $query->where('fund_id', $request->integer('fund_id'));
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

        $plan->load(['fund', 'category', 'activeRule']);

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

        $data = $request->validated();
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        $plan = Plan::create($data);

        $plan->load(['fund', 'category', 'activeRule']);

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

        $data = $request->validated();
        $data['updated_by'] = $user->id;

        $plan->update($data);

        $plan->load(['fund', 'category', 'activeRule']);

        return response()->json([
            'message' => 'Plan updated successfully.',
            'data' => new PlanResource($plan),
        ]);
    }

    public function storeRule(
        StorePlanRuleRequest $request,
        Plan $plan,
        PlanRuleService $planRuleService
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user->hasRole('super-admin') || $user->can('manage plans'),
            403
        );

        $planRuleService->createRule(
            plan: $plan,
            data: $request->validated(),
            userId: $user->id,
        );

        $plan->load(['fund', 'category', 'activeRule']);

        return response()->json([
            'message' => 'Plan rule created successfully.',
            'data' => new PlanResource($plan),
        ], 201);
    }

    public function updateRule(
        UpdatePlanRuleRequest $request,
        Plan $plan,
        PlanRule $planRule,
        PlanRuleService $planRuleService
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user->hasRole('super-admin') || $user->can('manage plans'),
            403
        );

        abort_unless($planRule->plan_id === $plan->id, 404);

        $planRuleService->updateRule(
            plan: $plan,
            planRule: $planRule,
            data: $request->validated(),
            userId: $user->id,
        );

        $plan->load(['fund', 'category', 'activeRule']);

        return response()->json([
            'message' => 'Plan rule updated successfully.',
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