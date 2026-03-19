<?php

namespace App\Application\Services\Nav;

use Carbon\Carbon;
use App\Models\BusinessHoliday;

class BusinessCalendarService
{
    public function isHoliday(Carbon $date): bool
    {
        return BusinessHoliday::query()
            ->whereDate('holiday_date', $date->toDateString())
            ->where('is_active', true)
            ->where('status', 'active')
            ->exists();
    }

    public function isBusinessDay(Carbon $date): bool
    {
        return ! $date->isWeekend() && ! $this->isHoliday($date);
    }

    public function nextBusinessDay(Carbon $date): Carbon
    {
        $candidate = $date->copy();

        while (! $this->isBusinessDay($candidate)) {
            $candidate->addDay();
        }

        return $candidate;
    }

    public function previousBusinessDay(Carbon $date): Carbon
    {
        $candidate = $date->copy();

        while (! $this->isBusinessDay($candidate)) {
            $candidate->subDay();
        }

        return $candidate;
    }

    public function normalizeToBusinessDay(Carbon $date): Carbon
    {
        return $this->isBusinessDay($date) ? $date : $this->nextBusinessDay($date);
    }
}