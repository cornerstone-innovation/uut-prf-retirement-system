<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\MarketSecurity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Application\Services\MarketData\DseMarketDataService;

class MarketSecurityController extends Controller
{
    public function search(
        Request $request,
        DseMarketDataService $dseMarketDataService
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('view plans') || auth()->user()?->can('manage plans'),
            403
        );

        $query = $request->string('q')->toString();

        $results = $dseMarketDataService->searchEquities($query);

        return response()->json([
            'message' => 'Market securities retrieved successfully.',
            'data' => $results,
        ]);
    }

    public function syncSelected(
        Request $request,
        DseMarketDataService $dseMarketDataService
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('update plans') || auth()->user()?->can('manage plans'),
            403
        );

        $validated = $request->validate([
            'source_security_reference' => ['nullable', 'string'],
            'symbol' => ['required', 'string'],
        ]);

        $rows = $dseMarketDataService->searchEquities($validated['symbol']);

        $match = collect($rows)->first(function (array $row) use ($validated) {
            if (! empty($validated['source_security_reference'])) {
                return $row['source_security_reference'] === $validated['source_security_reference'];
            }

            return strtoupper((string) $row['symbol']) === strtoupper((string) $validated['symbol']);
        });

        if (! $match) {
            return response()->json([
                'message' => 'Selected security was not found in DSE market data.',
            ], 404);
        }

        $security = MarketSecurity::query()->updateOrCreate(
            [
                'source' => 'dse',
                'source_security_reference' => $match['source_security_reference'] ?: $match['symbol'],
            ],
            [
                'uuid' => MarketSecurity::query()
                    ->where('source', 'dse')
                    ->where('source_security_reference', $match['source_security_reference'] ?: $match['symbol'])
                    ->value('uuid') ?: (string) Str::uuid(),
                'symbol' => $match['symbol'],
                'security_id' => $match['security_id'],
                'company_name' => $match['company_name'],
                'security_type' => $match['security_type'],
                'market_segment' => $match['market_segment'],
                'is_active' => true,
                'last_synced_at' => now(),
                'raw_payload' => $match['raw_payload'],
            ]
        );

        return response()->json([
            'message' => 'Market security synced successfully.',
            'data' => [
                'id' => $security->id,
                'uuid' => $security->uuid,
                'symbol' => $security->symbol,
                'security_id' => $security->security_id,
                'company_name' => $security->company_name,
                'security_type' => $security->security_type,
                'market_segment' => $security->market_segment,
                'source_security_reference' => $security->source_security_reference,
                'last_synced_at' => optional($security->last_synced_at)?->toDateTimeString(),
            ],
        ]);
    }
}