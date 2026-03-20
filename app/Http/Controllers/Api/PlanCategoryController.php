<?php

namespace App\Http\Controllers\Api;

use App\Models\PlanCategory;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class PlanCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = PlanCategory::query()
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name', 'description']);

        return response()->json([
            'message' => 'Plan categories retrieved successfully.',
            'data' => $categories,
        ]);
    }
}