<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\PlanNavRunLog;
use App\Application\Services\Nav\CalculateAndCreateNavRecordService;

class CalculateDailyPlanNavsDynamic extends Command
{
    protected $signature = 'nav:calculate-daily-dynamic';
    protected $description = 'Dynamically calculate daily NAV for plans based on configured market close times';

    public function handle(CalculateAndCreateNavRecordService $calculateAndCreateNavRecordService): int
    {
        $plans = Plan::query()
            ->with('configuration')
            ->whereHas('configuration', function ($query) {
                $query->where('auto_calculate_nav', true);
            })
            ->get();

        if ($plans->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($plans as $plan) {
            $config = $plan->configuration;

            if (! $config) {
                continue;
            }

            $timezone = $config->market_close_timezone ?: 'Africa/Dar_es_Salaam';
            $closeTime = $config->market_close_time ?: '16:00:00';

            $now = now($timezone);
            $navDueAt = Carbon::parse($closeTime, $timezone)->addMinutes(20);
            $valuationDate = $now->toDateString();

            if ($now->format('Y-m-d H:i') !== $navDueAt->format('Y-m-d H:i')) {
                continue;
            }

            $alreadyRun = PlanNavRunLog::query()
                ->where('plan_id', $plan->id)
                ->whereDate('valuation_date', $valuationDate)
                ->exists();

            if ($alreadyRun) {
                continue;
            }

            try {
                $result = $calculateAndCreateNavRecordService->execute(
                    plan: $plan,
                    valuationDate: $valuationDate,
                    createdBy: null,
                    notes: 'Auto-generated from nav:calculate-daily-dynamic command.',
                );

                PlanNavRunLog::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'plan_id' => $plan->id,
                    'valuation_date' => $valuationDate,
                    'executed_at' => now($timezone),
                    'status' => 'completed',
                    'message' => $result['nav_record_created']
                        ? 'NAV calculated and NAV record created successfully.'
                        : 'NAV calculated successfully. NAV record already existed.',
                    'metadata' => [
                        'nav_per_unit' => $result['snapshot']->nav_per_unit,
                        'timezone' => $timezone,
                        'nav_record_id' => $result['nav_record']->id ?? null,
                        'nav_record_created' => $result['nav_record_created'],
                    ],
                ]);

                $this->info(
                    "Calculated NAV for {$plan->name} ({$plan->code}) = {$result['snapshot']->nav_per_unit}" .
                    ($result['nav_record_created'] ? ' [NAV record created]' : ' [NAV record already existed]')
                );
            } catch (\Throwable $e) {
                PlanNavRunLog::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'plan_id' => $plan->id,
                    'valuation_date' => $valuationDate,
                    'executed_at' => now($timezone),
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                    'metadata' => [
                        'timezone' => $timezone,
                    ],
                ]);

                $this->error("Failed for {$plan->name} ({$plan->code}): " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}