<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\MarketSecurity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
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

        try {
            $query = $request->string('q')->toString();
            $results = $dseMarketDataService->searchEquities($query);

            return response()->json([
                'message' => 'Market securities retrieved successfully.',
                'data' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('Market security search failed.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve market securities.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncSelected(
        Request $request,
        DseMarketDataService $dseMarketDataService
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('update plans') || auth()->user()?->can('manage plans'),
            403
        );

        try {
            $validated = $request->validate([
                'source_security_reference' => ['nullable', 'string'],
                'symbol' => ['required', 'string'],
            ]);

            $rows = $dseMarketDataService->searchEquities($validated['symbol']);

            $match = collect($rows)->first(function (array $row) use ($validated) {
                if (! empty($validated['source_security_reference'])) {
                    return ($row['source_security_reference'] ?? null) === $validated['source_security_reference'];
                }

                return strtoupper((string) ($row['symbol'] ?? '')) === strtoupper((string) $validated['symbol']);
            });

            if (! $match) {
                return response()->json([
                    'message' => 'Selected security was not found in DSE market data.',
                ], 404);
            }

            $reference = $match['source_security_reference'] ?: $match['symbol'];

            $existingUuid = MarketSecurity::query()
                ->where('source', 'dse')
                ->where('source_security_reference', $reference)
                ->value('uuid');

            $security = MarketSecurity::query()->updateOrCreate(
                [
                    'source' => 'dse',
                    'source_security_reference' => $reference,
                ],
                [
                    'uuid' => $existingUuid ?: (string) Str::uuid(),
                    'symbol' => $match['symbol'] ?? null,
                    'security_id' => $match['security_id'] ?? null,
                    'company_name' => $match['company_name'] ?? null,
                    'security_type' => $match['security_type'] ?? null,
                    'market_segment' => $match['market_segment'] ?? null,
                    'is_active' => true,
                    'last_synced_at' => now(),
                    'raw_payload' => $match['raw_payload'] ?? null,
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
        } catch (\Throwable $e) {
            Log::error('Market security sync failed.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Failed to sync market security.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}