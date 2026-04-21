<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Application\Services\Nav\CalculateAndCreateNavRecordService;

class CalculateDailyPlanNavs extends Command
{
    protected $signature = 'nav:calculate-daily {--date=}';
    protected $description = 'Calculate daily NAV for plans configured for automatic NAV calculation';

    public function handle(CalculateAndCreateNavRecordService $calculateAndCreateNavRecordService): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->toDateString()
            : now()->toDateString();

        $plans = Plan::query()
            ->with('configuration')
            ->whereHas('configuration', function ($query) {
                $query->where('auto_calculate_nav', true);
            })
            ->get();

        if ($plans->isEmpty()) {
            $this->warn('No plans found with auto NAV calculation enabled.');
            return self::SUCCESS;
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($plans as $plan) {
            try {
                $result = $calculateAndCreateNavRecordService->execute(
                    plan: $plan,
                    valuationDate: $date,
                    createdBy: null,
                    notes: 'Auto-generated from nav:calculate-daily command.',
                );

                $this->info(
                    "Calculated NAV for {$plan->name} ({$plan->code}) = {$result['snapshot']->nav_per_unit}" .
                    ($result['nav_record_created'] ? ' [NAV record created]' : ' [NAV record already existed]')
                );

                $successCount++;
            } catch (\Throwable $e) {
                $this->error("Failed for {$plan->name} ({$plan->code}): " . $e->getMessage());
                $failedCount++;
            }
        }

        $this->line("Date: {$date}");
        $this->line("Success: {$successCount}");
        $this->line("Failed: {$failedCount}");

        return self::SUCCESS;
    }
}