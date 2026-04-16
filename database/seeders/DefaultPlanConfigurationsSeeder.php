<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanConfiguration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultPlanConfigurationsSeeder extends Seeder
{
    public function run(): void
    {
        $configurations = [
            'YOUNGSTERS' => [
                'plan_family' => 'youngsters',
                'valuation_method' => 'fixed_offer_then_equity',
                'phase_status' => 'initial_offer',
                'market_close_time' => '16:00:00',
                'market_close_timezone' => 'Africa/Dar_es_Salaam',
                'auto_calculate_nav' => true,
                'allow_nav_override' => true,
                'allow_phase_override' => true,
                'is_phase_overridden' => false,
            ],
            'MIDDLEAGE' => [
                'plan_family' => 'middle_age',
                'valuation_method' => 'fixed_offer_then_equity_bonds',
                'phase_status' => 'initial_offer',
                'market_close_time' => '16:00:00',
                'market_close_timezone' => 'Africa/Dar_es_Salaam',
                'auto_calculate_nav' => true,
                'allow_nav_override' => true,
                'allow_phase_override' => true,
                'is_phase_overridden' => false,
            ],
            'SENIORS' => [
                'plan_family' => 'seniors',
                'valuation_method' => 'fixed_offer_then_bonds',
                'phase_status' => 'initial_offer',
                'market_close_time' => '16:00:00',
                'market_close_timezone' => 'Africa/Dar_es_Salaam',
                'auto_calculate_nav' => true,
                'allow_nav_override' => true,
                'allow_phase_override' => true,
                'is_phase_overridden' => false,
            ],
        ];

        foreach ($configurations as $planCode => $configData) {
            $plan = Plan::query()->where('code', $planCode)->first();

            if (! $plan) {
                $this->command?->warn("Plan {$planCode} not found. Run DefaultPlansSeeder first.");
                continue;
            }

            PlanConfiguration::query()->updateOrCreate(
                ['plan_id' => $plan->id],
                array_merge($configData, [
                    'uuid' => PlanConfiguration::query()->where('plan_id', $plan->id)->value('uuid') ?: (string) Str::uuid(),
                ])
            );
        }
    }
}