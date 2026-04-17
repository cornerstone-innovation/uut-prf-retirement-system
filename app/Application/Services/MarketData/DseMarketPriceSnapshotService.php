<?php

namespace App\Application\Services\MarketData;

use App\Models\MarketSecurity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\MarketSecurityPriceSnapshot;

class DseMarketPriceSnapshotService
{
    public function syncToday(): array
    {
        $rows = app(DseMarketDataService::class)->fetchEquities();

        $synced = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $reference = $row['source_security_reference'] ?? null;
            $symbol = $row['symbol'] ?? null;

            if (! $reference && ! $symbol) {
                $skipped++;
                continue;
            }

            $security = MarketSecurity::query()
                ->when($reference, fn ($q) => $q->where('source_security_reference', $reference))
                ->when(! $reference && $symbol, fn ($q) => $q->where('symbol', $symbol))
                ->first();

            if (! $security) {
                $skipped++;
                continue;
            }

            $capturedAt = ! empty($row['captured_at'])
                ? Carbon::parse($row['captured_at'])
                : now();

            $priceDate = $capturedAt->toDateString();

            $existingUuid = MarketSecurityPriceSnapshot::query()
                ->where('market_security_id', $security->id)
                ->whereDate('price_date', $priceDate)
                ->value('uuid');

            MarketSecurityPriceSnapshot::query()->updateOrCreate(
                [
                    'market_security_id' => $security->id,
                    'price_date' => $priceDate,
                ],
                [
                    'uuid' => $existingUuid ?: (string) Str::uuid(),
                    'captured_at' => $capturedAt,
                    'market_price' => $row['market_price'] ?? 0,
                    'opening_price' => $row['opening_price'] ?? null,
                    'change_amount' => $row['change'] ?? null,
                    'percentage_change' => $row['percentage_change'] ?? null,
                    'high_price' => $row['high'] ?? null,
                    'low_price' => $row['low'] ?? null,
                    'volume' => $row['volume'] ?? null,
                    'source' => 'dse',
                    'raw_payload' => $row['raw_payload'] ?? null,
                ]
            );

            $synced++;
        }

        return [
            'synced_count' => $synced,
            'skipped_count' => $skipped,
            'snapshot_date' => now()->toDateString(),
        ];
    }
}