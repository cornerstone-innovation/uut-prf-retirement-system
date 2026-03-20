<?php

namespace App\Http\Controllers\Api;

use App\Models\Fund;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class FundController extends Controller
{
    public function index(): JsonResponse
    {
        $funds = Fund::query()
            ->orderBy('name')
            ->get(['id', 'uuid', 'code', 'name', 'description', 'status']);

        return response()->json([
            'message' => 'Funds retrieved successfully.',
            'data' => $funds,
        ]);
    }
}