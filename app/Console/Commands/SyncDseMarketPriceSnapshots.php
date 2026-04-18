<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Application\Services\MarketData\DseMarketPriceSnapshotService;

class SyncDseMarketPriceSnapshots extends Command
{
    protected $signature = 'market-data:sync-dse-price-snapshots';
    protected $description = 'Sync daily DSE market price snapshots for locally tracked market securities';

    public function handle(DseMarketPriceSnapshotService $snapshotService): int
    {
        $result = $snapshotService->syncToday();

        $this->info('DSE market price snapshot sync completed.');
        $this->line('Synced: ' . $result['synced_count']);
        $this->line('Skipped: ' . $result['skipped_count']);
        $this->line('Snapshot date: ' . $result['snapshot_date']);

        return self::SUCCESS;
    }
}