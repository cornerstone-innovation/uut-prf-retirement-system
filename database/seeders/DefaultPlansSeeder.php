<?php

namespace Database\Seeders;

use App\Models\Fund;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultPlansSeeder extends Seeder
{
    public function run(): void
    {
        $fund = Fund::query()->first();
        $category = PlanCategory::query()->first();
        $admin = User::query()->first();

        if (! $fund) {
            $this->command?->warn('No fund found. Create a fund first before seeding default plans.');
            return;
        }

        if (! $category) {
            $this->command?->warn('No plan category found. Create at least one plan category first before seeding default plans.');
            return;
        }

        $plans = [
            [
                'code' => 'YOUNGSTERS',
                'name' => 'Youngsters Plan',
                'description' => 'Youth-focused unit trust plan with fixed initial offer phase followed by equity-based market valuation.',
                'investment_objective' => 'Long-term capital growth through equity-focused investing after the initial fixed-price offer period.',
                'target_audience' => 'Young investors and guardians investing for younger beneficiaries.',
            ],
            [
                'code' => 'MIDDLEAGE',
                'name' => 'Middle Age Plan',
                'description' => 'Balanced unit trust plan with fixed initial offer phase followed by equity and government bond valuation.',
                'investment_objective' => 'Balanced growth and income through a mix of listed equities and government bonds.',
                'target_audience' => 'Middle-aged investors seeking a balance of growth and stability.',
            ],
            [
                'code' => 'SENIORS',
                'name' => 'Seniors Plan',
                'description' => 'Income-oriented unit trust plan with fixed initial offer phase followed by bond-based valuation.',
                'investment_objective' => 'Capital preservation and income generation through government bond allocations.',
                'target_audience' => 'Senior investors seeking stability and predictable income.',
            ],
        ];

        foreach ($plans as $planData) {
            Plan::query()->updateOrCreate(
                ['code' => $planData['code']],
                [
                    'uuid' => Plan::query()->where('code', $planData['code'])->value('uuid') ?: (string) Str::uuid(),
                    'fund_id' => $fund->id,
                    'plan_category_id' => $category->id,
                    'name' => $planData['name'],
                    'description' => $planData['description'],
                    'status' => 'draft',
                    'is_default' => true,
                    'investment_objective' => $planData['investment_objective'],
                    'target_audience' => $planData['target_audience'],
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ]
            );
        }
    }
}