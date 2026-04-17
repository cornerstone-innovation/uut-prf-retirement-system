<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Services\MarketData\DseMarketPriceSnapshotService;

class MarketSecurityPriceSnapshotController extends Controller
{
    public function sync(DseMarketPriceSnapshotService $snapshotService): JsonResponse
    {
        $result = $snapshotService->syncToday();

        return response()->json([
            'message' => 'Market security price snapshots synced successfully.',
            'data' => $result,
        ]);
    }
}