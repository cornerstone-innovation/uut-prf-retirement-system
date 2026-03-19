<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\CutoffTimeRule;
use Illuminate\Validation\ValidationException;

class CutoffTimeRuleService
{
    public function create(
        ?Plan $plan,
        string $cutoffTime,
        string $timezone,
        string $effectiveFrom,
        ?string $effectiveTo = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): CutoffTimeRule {
        return DB::transaction(function () use (
            $plan,
            $cutoffTime,
            $timezone,
            $effectiveFrom,
            $effectiveTo,
            $notes,
            $createdBy
        ) {
            return CutoffTimeRule::create([
                'uuid' => (string) Str::uuid(),
                'plan_id' => $plan?->id,
                'cutoff_time' => $cutoffTime,
                'timezone' => $timezone,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'status' => 'draft',
                'is_active' => false,
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function approve(
        CutoffTimeRule $rule,
        int $actedBy,
        ?string $notes = null
    ): CutoffTimeRule {
        return DB::transaction(function () use ($rule, $actedBy, $notes) {
            if ((int) $rule->created_by === (int) $actedBy) {
                throw ValidationException::withMessages([
                    'approval' => ['Creator cannot approve their own cutoff rule.'],
                ]);
            }

            $rule->update([
                'status' => 'approved',
                'approved_by' => $actedBy,
                'approved_at' => now(),
                'notes' => $notes ?: $rule->notes,
            ]);

            return $rule->fresh();
        });
    }

    public function activate(
        CutoffTimeRule $rule,
        ?string $notes = null
    ): CutoffTimeRule {
        return DB::transaction(function () use ($rule, $notes) {
            if ($rule->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => ['Only approved cutoff rules can be activated.'],
                ]);
            }

            CutoffTimeRule::query()
                ->where('id', '!=', $rule->id)
                ->where('plan_id', $rule->plan_id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'status' => 'inactive',
                ]);

            $rule->update([
                'is_active' => true,
                'status' => 'active',
                'notes' => $notes ?: $rule->notes,
            ]);

            return $rule->fresh();
        });
    }
}