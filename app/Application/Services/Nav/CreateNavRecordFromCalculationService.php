<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use App\Models\NavRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateNavRecordFromCalculationService
{
    public function create(
        Plan $plan,
        array $calculationData,
        ?int $createdBy = null,
        ?string $notes = null
    ): NavRecord {
        return DB::transaction(function () use ($plan, $calculationData, $createdBy, $notes) {
            $existing = NavRecord::query()
                ->where('plan_id', $plan->id)
                ->whereDate('valuation_date', $calculationData['valuation_date'])
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'valuation_date' => ['A NAV record already exists for this plan and valuation date.'],
                ]);
            }

            return NavRecord::create([
                'uuid' => (string) Str::uuid(),
                'plan_id' => $plan->id,
                'valuation_date' => $calculationData['valuation_date'],
                'nav_per_unit' => $calculationData['nav_per_unit'],
                'status' => 'pending_approval',
                'source' => 'calculation_engine',
                'notes' => $notes,
                'created_by' => $createdBy,
                'updated_by' => $createdBy,
                'metadata' => [
                    'calculation_source' => 'calculation_engine',
                    'calculated_at' => now()->toDateTimeString(),
                    'net_asset_value' => $calculationData['net_asset_value'],
                    'outstanding_units' => $calculationData['outstanding_units'],
                    'price_source' => $calculationData['price_source'],
                    'breakdown' => $calculationData['breakdown'],
                ],
            ]);
        });
    }
}