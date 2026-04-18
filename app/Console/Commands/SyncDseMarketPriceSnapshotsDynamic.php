<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\MarketDataSyncRun;
use App\Models\PlanConfiguration;
use App\Application\Services\MarketData\DseMarketPriceSnapshotService;

class SyncDseMarketPriceSnapshotsDynamic extends Command
{
    protected $signature = 'market-data:sync-dse-price-snapshots-dynamic';
    protected $description = 'Dynamically sync DSE market price snapshots based on configured market close times';

    public function handle(DseMarketPriceSnapshotService $snapshotService): int
    {
        $configs = PlanConfiguration::query()
            ->where('auto_calculate_nav', true)
            ->get();

        if ($configs->isEmpty()) {
            return self::SUCCESS;
        }

        $shouldRun = false;
        $selectedTimezone = 'Africa/Dar_es_Salaam';
        $today = now($selectedTimezone)->toDateString();

        foreach ($configs as $config) {
            $timezone = $config->market_close_timezone ?: 'Africa/Dar_es_Salaam';
            $closeTime = $config->market_close_time ?: '16:00:00';

            $now = now($timezone);
            $syncDueAt = Carbon::parse($closeTime, $timezone)->addMinutes(10);

            if ($now->format('Y-m-d H:i') === $syncDueAt->format('Y-m-d H:i')) {
                $shouldRun = true;
                $selectedTimezone = $timezone;
                $today = $now->toDateString();
                break;
            }
        }

        if (! $shouldRun) {
            return self::SUCCESS;
        }

        $alreadyRun = MarketDataSyncRun::query()
            ->where('sync_type', 'dse_price_snapshot')
            ->whereDate('run_date', $today)
            ->exists();

        if ($alreadyRun) {
            return self::SUCCESS;
        }

        $result = $snapshotService->syncToday();

        MarketDataSyncRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'sync_type' => 'dse_price_snapshot',
            'run_date' => $today,
            'timezone' => $selectedTimezone,
            'executed_at' => now($selectedTimezone),
            'metadata' => $result,
        ]);

        $this->info('Dynamic DSE market sync completed.');
        $this->line('Synced: ' . $result['synced_count']);
        $this->line('Skipped: ' . $result['skipped_count']);

        return self::SUCCESS;
    }
}