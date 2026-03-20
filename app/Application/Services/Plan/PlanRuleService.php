<?php

namespace App\Application\Services\Plan;

use App\Models\Plan;
use App\Models\PlanRule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PlanRuleService
{
    public function createRule(Plan $plan, array $data, ?int $userId = null): PlanRule
    {
        return DB::transaction(function () use ($plan, $data, $userId) {
            $shouldBeActive = (bool)($data['is_active'] ?? true);
            $status = $data['status'] ?? ($shouldBeActive ? 'active' : 'draft');

            if ($shouldBeActive) {
                $plan->rules()->update([
                    'is_active' => false,
                    'status' => 'inactive',
                    'updated_by' => $userId,
                ]);
            }

            return $plan->rules()->create([
                'uuid' => (string) Str::uuid(),
                'minimum_initial_investment' => $data['minimum_initial_investment'] ?? null,
                'maximum_initial_investment' => $data['maximum_initial_investment'] ?? null,
                'minimum_additional_investment' => $data['minimum_additional_investment'] ?? null,
                'maximum_additional_investment' => $data['maximum_additional_investment'] ?? null,
                'minimum_redemption_amount' => $data['minimum_redemption_amount'] ?? null,
                'minimum_balance_after_redemption' => $data['minimum_balance_after_redemption'] ?? null,
                'lock_in_period_years' => $data['lock_in_period_years'] ?? 0,
                'switching_allowed' => $data['switching_allowed'] ?? false,
                'sip_allowed' => $data['sip_allowed'] ?? false,
                'minimum_sip_amount' => $data['minimum_sip_amount'] ?? null,
                'sip_frequency' => $data['sip_frequency'] ?? null,
                'option_growth' => $data['option_growth'] ?? true,
                'option_dividend' => $data['option_dividend'] ?? false,
                'option_dividend_reinvestment' => $data['option_dividend_reinvestment'] ?? false,
                'exit_fee_percentage' => $data['exit_fee_percentage'] ?? null,
                'exit_fee_period_days' => $data['exit_fee_period_days'] ?? null,
                'currency' => $data['currency'] ?? 'TZS',
                'status' => $status,
                'is_active' => $shouldBeActive,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        });
    }

    public function updateRule(Plan $plan, PlanRule $planRule, array $data, ?int $userId = null): PlanRule
    {
        return DB::transaction(function () use ($plan, $planRule, $data, $userId) {
            $shouldBeActive = (bool)($data['is_active'] ?? $planRule->is_active);

            if ($shouldBeActive) {
                $plan->rules()
                    ->whereKeyNot($planRule->id)
                    ->update([
                        'is_active' => false,
                        'status' => 'inactive',
                        'updated_by' => $userId,
                    ]);
            }

            $planRule->update(array_merge($data, [
                'updated_by' => $userId,
                'status' => $data['status'] ?? ($shouldBeActive ? 'active' : $planRule->status),
                'is_active' => $shouldBeActive,
            ]));

            return $planRule->fresh();
        });
    }
}