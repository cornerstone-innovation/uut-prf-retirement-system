<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Services\Dashboard\AdminDashboardService;

class AdminDashboardController extends Controller
{
    public function summary(
        Request $request,
        AdminDashboardService $dashboardService
    ): JsonResponse {
        $user = $request->user();

        abort_unless($user, 401);

        if ($user->hasRole('investor')) {
            return response()->json([
                'message' => 'This dashboard is for internal users only.',
            ], 403);
        }

        return response()->json([
            'message' => 'Admin dashboard summary retrieved successfully.',
            'data' => $dashboardService->summary(),
        ]);
    }
}