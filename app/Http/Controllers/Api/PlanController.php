<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Application\Services\Plan\PlanEligibilityService;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Plan\CheckPurchaseEligibilityRequest;
use App\Http\Requests\Plan\PurchasePreviewRequest;
use App\Application\Services\Purchase\PurchasePreviewService;

class PlanController extends Controller
{
    public function index(
        Request $request,
        PlanEligibilityService $eligibilityService
    ): JsonResponse {
        $investor = $request->user()?->investor;

        if (! $investor) {
            throw ValidationException::withMessages([
                'investor' => ['Authenticated user is not linked to an investor profile.'],
            ]);
        }

        $eligibilityService->ensureCanViewProducts($investor);

        $query = Plan::query()
            ->with(['category', 'activeRule'])
            ->whereIn('status', ['approved', 'active']);

        if ($request->filled('category_code')) {
            $categoryCode = $request->string('category_code')->toString();

            $query->whereHas('category', function ($q) use ($categoryCode) {
                $q->where('code', $categoryCode);
            });
        }

        $plans = $query->orderBy('id')->get();

        return response()->json([
            'message' => 'Plans retrieved successfully.',
            'data' => PlanResource::collection($plans),
        ]);
    }

    public function show(
        Request $request,
        Plan $plan,
        PlanEligibilityService $eligibilityService
    ): JsonResponse {
        $investor = $request->user()?->investor;

        if (! $investor) {
            throw ValidationException::withMessages([
                'investor' => ['Authenticated user is not linked to an investor profile.'],
            ]);
        }

        $eligibilityService->ensureCanViewProducts($investor);

        $plan->load(['category', 'activeRule']);

        return response()->json([
            'message' => 'Plan retrieved successfully.',
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