<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanRule;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class PlanRuleSeeder extends Seeder
{
    public function run(): void
    {
        $youngsters = Plan::where('code', 'UUT-YOUNGSTERS')->first();
        $middleAgers = Plan::where('code', 'UUT-MIDDLE-AGERS')->first();
        $seniors = Plan::where('code', 'UUT-SENIORS')->first();

        if ($youngsters) {
            PlanRule::updateOrCreate(
                ['plan_id' => $youngsters->id, 'is_active' => true],
                [
                    'uuid' => (string) Str::uuid(),
                    'minimum_initial_investment' => 10000,
                    'minimum_additional_investment' => 10000,
                    'minimum_redemption_amount' => 10000,
                    'minimum_balance_after_redemption' => 10000,
                    'lock_in_period_years' => 5,
                    'switching_allowed' => true,
                    'sip_allowed' => true,
                    'minimum_sip_amount' => 10000,
                    'option_growth' => true,
                    'option_dividend' => true,
                    'option_dividend_reinvestment' => true,
                    'is_active' => true,
                ]
            );
        }

        if ($middleAgers) {
            PlanRule::updateOrCreate(
                ['plan_id' => $middleAgers->id, 'is_active' => true],
                [
                    'uuid' => (string) Str::uuid(),
                    'minimum_initial_investment' => 10000,
                    'minimum_additional_investment' => 10000,
                    'minimum_redemption_amount' => 10000,
                    'minimum_balance_after_redemption' => 10000,
                    'lock_in_period_years' => 5,
                    'switching_allowed' => true,
                    'sip_allowed' => true,
                    'minimum_sip_amount' => 10000,
                    'option_growth' => true,
                    'option_dividend' => true,
                    'option_dividend_reinvestment' => true,
                    'is_active' => true,
                ]
            );
        }

        if ($seniors) {
            PlanRule::updateOrCreate(
                ['plan_id' => $seniors->id, 'is_active' => true],
                [
                    'uuid' => (string) Str::uuid(),
                    'minimum_initial_investment' => 10000,
                    'minimum_additional_investment' => 10000,
                    'minimum_redemption_amount' => 10000,
                    'minimum_balance_after_redemption' => 10000,
                    'lock_in_period_years' => 5,
                    'switching_allowed' => true,
                    'sip_allowed' => true,
                    'minimum_sip_amount' => 10000,
                    'option_growth' => true,
                    'option_dividend' => true,
                    'option_dividend_reinvestment' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}