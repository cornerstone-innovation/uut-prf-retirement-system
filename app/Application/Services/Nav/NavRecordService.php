<?php

namespace App\Application\Services\Nav;

use App\Models\NavRecord;
use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NavRecordService
{
    public function create(
        Plan $plan,
        string $valuationDate,
        float $navPerUnit,
        ?string $notes = null,
        ?int $createdBy = null
    ): NavRecord {
        return DB::transaction(function () use ($plan, $valuationDate, $navPerUnit, $notes, $createdBy) {
            $existing = NavRecord::query()
                ->where('plan_id', $plan->id)
                ->whereDate('valuation_date', $valuationDate)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'valuation_date' => ['A NAV record already exists for this plan and valuation date.'],
                ]);
            }

            return NavRecord::create([
                'uuid' => (string) Str::uuid(),
                'plan_id' => $plan->id,
                'valuation_date' => $valuationDate,
                'nav_per_unit' => $navPerUnit,
                'status' => 'pending_approval',
                'source' => 'manual',
                'notes' => $notes,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function approve(
        NavRecord $navRecord,
        int $actedBy,
        ?string $notes = null
    ): NavRecord {
        return DB::transaction(function () use ($navRecord, $actedBy, $notes) {
            if ((int) $navRecord->created_by === (int) $actedBy) {
                throw ValidationException::withMessages([
                    'approval' => ['Creator cannot approve their own NAV record.'],
                ]);
            }

            if ($navRecord->status === 'published') {
                throw ValidationException::withMessages([
                    'status' => ['Published NAV cannot be approved again.'],
                ]);
            }

            if ($navRecord->approved_by_1 && (int) $navRecord->approved_by_1 === (int) $actedBy) {
                throw ValidationException::withMessages([
                    'approval' => ['The same approver cannot approve the NAV record twice.'],
                ]);
            }

            if (! $navRecord->approved_by_1) {
                $navRecord->update([
                    'approved_by_1' => $actedBy,
                    'approved_at_1' => now(),
                    'status' => 'partially_approved',
                    'notes' => $notes ?: $navRecord->notes,
                ]);

                return $navRecord->fresh();
            }

            if (! $navRecord->approved_by_2) {
                $navRecord->update([
                    'approved_by_2' => $actedBy,
                    'approved_at_2' => now(),
                    'status' => 'approved',
                    'notes' => $notes ?: $navRecord->notes,
                ]);

                return $navRecord->fresh();
            }

            throw ValidationException::withMessages([
                'approval' => ['NAV record already has two approvals.'],
            ]);
        });
    }

    public function publish(
        NavRecord $navRecord,
        int $actedBy,
        ?string $notes = null
    ): NavRecord {
        return DB::transaction(function () use ($navRecord, $actedBy, $notes) {
            if ($navRecord->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => ['Only fully approved NAV records can be published.'],
                ]);
            }

            $navRecord->update([
                'status' => 'published',
                'published_by' => $actedBy,
                'published_at' => now(),
                'notes' => $notes ?: $navRecord->notes,
            ]);

            return $navRecord->fresh();
        });
    }

    public function getLatestPublishedNav(Plan $plan): ?NavRecord
    {
        return NavRecord::query()
            ->where('plan_id', $plan->id)
            ->where('status', 'published')
            ->orderByDesc('valuation_date')
            ->first();
    }

    public function getPublishedNavForDate(Plan $plan, string $valuationDate): ?NavRecord
    {
        return NavRecord::query()
            ->where('plan_id', $plan->id)
            ->whereDate('valuation_date', $valuationDate)
            ->where('status', 'published')
            ->first();
    }
}