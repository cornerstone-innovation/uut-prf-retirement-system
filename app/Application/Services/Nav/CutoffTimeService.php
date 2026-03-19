<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use Carbon\Carbon;
use App\Models\CutoffTimeRule;
use Illuminate\Validation\ValidationException;

class CutoffTimeService
{
    public function __construct(
        private readonly BusinessCalendarService $businessCalendarService
    ) {
    }

    public function getApplicableRule(Plan $plan, ?Carbon $asOf = null): ?CutoffTimeRule
    {
        $asOf = $asOf ?: now();

        $date = $asOf->toDateString();

        $planRule = CutoffTimeRule::query()
            ->where('plan_id', $plan->id)
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            })
            ->latest('effective_from')
            ->first();

        if ($planRule) {
            return $planRule;
        }

        return CutoffTimeRule::query()
            ->whereNull('plan_id')
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            })
            ->latest('effective_from')
            ->first();
    }

    public function resolvePricingDate(Plan $plan, ?Carbon $submittedAt = null): array
    {
        $submittedAt = $submittedAt ?: now();

        $rule = $this->getApplicableRule($plan, $submittedAt);

        if (! $rule) {
            throw ValidationException::withMessages([
                'cutoff' => ['No active cutoff time rule exists for this plan.'],
            ]);
        }

        $timezone = $rule->timezone ?: 'Africa/Dar_es_Salaam';
        $localSubmittedAt = $submittedAt->copy()->timezone($timezone);

        $submittedDate = Carbon::parse($localSubmittedAt->toDateString(), $timezone);
        $businessSubmissionDate = $this->businessCalendarService->normalizeToBusinessDay($submittedDate)->toDateString();

        $cutoffDateTime = Carbon::parse(
            $businessSubmissionDate . ' ' . $rule->cutoff_time,
            $timezone
        );

        $sameBusinessDay = $this->businessCalendarService->isBusinessDay(
            Carbon::parse($businessSubmissionDate, $timezone)
        );

        if ($sameBusinessDay && $localSubmittedAt->lessThanOrEqualTo($cutoffDateTime)) {
            $pricingDate = $businessSubmissionDate;
        } else {
            $pricingDate = $this->businessCalendarService
                ->nextBusinessDay(Carbon::parse($businessSubmissionDate, $timezone)->addDay())
                ->toDateString();
        }

        return [
            'pricing_date' => $pricingDate,
            'cutoff_rule_id' => $rule->id,
            'cutoff_time' => $rule->cutoff_time,
            'timezone' => $timezone,
            'submitted_at_local' => $localSubmittedAt->toDateTimeString(),
        ];
    }
}