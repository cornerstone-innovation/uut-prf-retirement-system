<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\PlanNavRunLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Application\Services\Nav\PlanNavScheduleService;
use App\Application\Services\Nav\RunAutomaticPlanNavService;

class RunAutomaticPlanNavs extends Command
{
    protected $signature = 'nav:run-auto';
    protected $description = 'Automatically calculate, record, approve, and publish NAV for eligible plans';

    public function handle(
        PlanNavScheduleService $planNavScheduleService,
        RunAutomaticPlanNavService $runAutomaticPlanNavService,
    ): int {
        $plans = Plan::query()
            ->with('configuration')
            ->whereHas('configuration', function ($query) {
                $query->where('auto_calculate_nav', true)
                    ->where('phase_status', 'live_nav');
            })
            ->get();

        if ($plans->isEmpty()) {
            $this->warn('No eligible plans found for automatic NAV.');
            return self::SUCCESS;
        }

        foreach ($plans as $plan) {
            $config = $plan->configuration;

            if (! $config) {
                continue;
            }

            $timezone = $config->market_close_timezone ?: 'Africa/Dar_es_Salaam';
            $marketCloseTime = $config->market_close_time;

            if (! $marketCloseTime) {
                continue;
            }

            $now = Carbon::now($timezone);
            [$hour, $minute] = array_map('intval', explode(':', substr($marketCloseTime, 0, 5)));

            $marketCloseAt = $now->copy()->setTime($hour, $minute, 0);
            $navDueAt = $marketCloseAt->copy()->addMinutes(20);
            $valuationDate = $marketCloseAt->toDateString();

            if ($now->lt($navDueAt)) {
                continue;
            }

            $alreadyRun = PlanNavRunLog::query()
                ->where('plan_id', $plan->id)
                ->whereDate('valuation_date', $valuationDate)
                ->where('status', 'completed')
                ->exists();

            if ($alreadyRun) {
                continue;
            }

            try {
                $result = $runAutomaticPlanNavService->run(
                    plan: $plan,
                    valuationDate: $valuationDate,
                    systemUserId: null,
                );

                PlanNavRunLog::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'plan_id' => $plan->id,
                    'valuation_date' => $valuationDate,
                    'executed_at' => now($timezone),
                    'status' => 'completed',
                    'message' => $result['already_published']
                        ? 'NAV was already published for this valuation date.'
                        : 'NAV calculated, recorded, approved, and published successfully.',
                    'metadata' => [
                        'timezone' => $timezone,
                        'nav_record_id' => $result['nav_record']?->id,
                        'nav_record_created' => $result['nav_record_created'] ?? false,
                        'already_published' => $result['already_published'] ?? false,
                        'nav_per_unit' => $result['snapshot']?->nav_per_unit,
                        'auto_allocation' => $result['auto_allocation'],
                    ],
                ]);

                $this->info("Automatic NAV completed for {$plan->name} ({$plan->code}).");
            } catch (\Throwable $e) {
                PlanNavRunLog::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'plan_id' => $plan->id,
                    'valuation_date' => now($timezone)->toDateString(),
                    'executed_at' => now($timezone),
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'metadata' => [
                        'timezone' => $timezone,
                    ],
                ]);

                $this->error("Failed for {$plan->name} ({$plan->code}): {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}