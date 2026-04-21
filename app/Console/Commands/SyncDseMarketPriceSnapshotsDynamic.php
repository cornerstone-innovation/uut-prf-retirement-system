<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\MarketDataSyncRun;
use App\Application\Services\MarketData\DseMarketPriceSnapshotService;

class SyncDseMarketPriceSnapshotsDynamic extends Command
{
    protected $signature = 'market-data:sync-dse-price-snapshots-dynamic';
    protected $description = 'Sync DSE market price snapshots on a recurring schedule';

    public function handle(DseMarketPriceSnapshotService $snapshotService): int
    {
        $timezone = 'Africa/Dar_es_Salaam';
        $today = now($timezone)->toDateString();

        try {
            $result = $snapshotService->syncToday();

            MarketDataSyncRun::query()->create([
                'uuid' => (string) Str::uuid(),
                'sync_type' => 'dse_price_snapshot',
                'run_date' => $today,
                'timezone' => $timezone,
                'executed_at' => now($timezone),
                'metadata' => [
                    ...$result,
                    'trigger' => 'scheduled_recurring_sync',
                ],
            ]);

            $this->info('DSE market sync completed.');
            $this->line('Synced: ' . ($result['synced_count'] ?? 0));
            $this->line('Skipped: ' . ($result['skipped_count'] ?? 0));
            $this->line('Snapshot date: ' . ($result['snapshot_date'] ?? $today));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            MarketDataSyncRun::query()->create([
                'uuid' => (string) Str::uuid(),
                'sync_type' => 'dse_price_snapshot',
                'run_date' => $today,
                'timezone' => $timezone,
                'executed_at' => now($timezone),
                'metadata' => [
                    'trigger' => 'scheduled_recurring_sync',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ],
            ]);

            $this->error('DSE market sync failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}