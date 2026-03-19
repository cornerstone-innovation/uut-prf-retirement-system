<?php

namespace Database\Seeders;

use App\Models\Fund;
use App\Models\Plan;
use App\Models\PlanCategory;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class FundAndPlanSeeder extends Seeder
{
    public function run(): void
    {
        $fund = Fund::firstOrCreate(
            ['code' => 'UUT-PRF'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'UUT Private Retirement Fund',
                'description' => 'Open-ended unit trust / private retirement fund.',
                'fund_type' => 'unit_trust',
                'pricing_method' => 'nav',
                'status' => 'draft',
                'currency' => 'TZS',
                'is_open_ended' => true,
            ]
        );

        $youngsters = PlanCategory::firstOrCreate(
            ['code' => 'youngsters'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Youngsters',
                'description' => 'Growth-oriented plan for younger investors.',
                'is_active' => true,
            ]
        );

        $middleAgers = PlanCategory::firstOrCreate(
            ['code' => 'middle_agers'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Middle-Agers',
                'description' => 'Balanced / income-oriented plan.',
                'is_active' => true,
            ]
        );

        $seniors = PlanCategory::firstOrCreate(
            ['code' => 'seniors'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Seniors',
                'description' => 'Capital preservation / gilt-oriented plan.',
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['code' => 'UUT-YOUNGSTERS'],
            [
                'uuid' => (string) Str::uuid(),
                'fund_id' => $fund->id,
                'plan_category_id' => $youngsters->id,
                'name' => 'Youngsters’ Plan',
                'description' => 'Growth-oriented retirement plan.',
                'status' => 'draft',
                'is_default' => false,
                'investment_objective' => 'Long-term capital growth.',
                'target_audience' => 'Younger investors with longer time horizon.',
            ]
        );

        Plan::firstOrCreate(
            ['code' => 'UUT-MIDDLE-AGERS'],
            [
                'uuid' => (string) Str::uuid(),
                'fund_id' => $fund->id,
                'plan_category_id' => $middleAgers->id,
                'name' => 'Middle-Agers’ Plan',
                'description' => 'Balanced retirement plan.',
                'status' => 'draft',
                'is_default' => false,
                'investment_objective' => 'Balanced income and growth.',
                'target_audience' => 'Middle-aged investors seeking balance.',
            ]
        );

        Plan::firstOrCreate(
            ['code' => 'UUT-SENIORS'],
            [
                'uuid' => (string) Str::uuid(),
                'fund_id' => $fund->id,
                'plan_category_id' => $seniors->id,
                'name' => 'Seniors’ Plan',
                'description' => 'Conservative retirement plan.',
                'status' => 'draft',
                'is_default' => false,
                'investment_objective' => 'Capital preservation and stable income.',
                'target_audience' => 'Senior investors seeking lower risk.',
            ]
        );
    }
}