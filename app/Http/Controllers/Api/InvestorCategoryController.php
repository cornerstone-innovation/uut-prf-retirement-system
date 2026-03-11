<?php

namespace App\Http\Controllers\Api;

use App\Models\InvestorCategory;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvestorCategoryResource;
use App\Application\Services\Document\DocumentRequirementService;

class InvestorCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = InvestorCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'message' => 'Investor categories retrieved successfully.',
            'data' => InvestorCategoryResource::collection($categories),
        ]);
    }

    public function documentRequirements(
        InvestorCategory $investorCategory,
        DocumentRequirementService $documentRequirementService
    ): JsonResponse {
        $category = $documentRequirementService->getRequirementsForCategory($investorCategory);

        return response()->json([
            'message' => 'Document requirements retrieved successfully.',
            'data' => new InvestorCategoryResource($category),
        ]);
    }
}