<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;
use Illuminate\Support\Carbon;

class PlanNavScheduleService
{
    public function getSchedule(Plan $plan): array
    {
        $timezone = $plan->configuration?->market_close_timezone ?: 'Africa/Dar_es_Salaam';
        $marketCloseTime = $plan->configuration?->market_close_time;

        if (! $marketCloseTime) {
            return [
                'timezone' => $timezone,
                'market_close_time' => null,
                'next_market_close_at' => null,
                'next_price_sync_at' => null,
                'next_nav_calculation_at' => null,
                'auto_calculate_nav' => (bool) $plan->configuration?->auto_calculate_nav,
            ];
        }

        $now = Carbon::now($timezone);

        [$hour, $minute] = array_map('intval', explode(':', substr($marketCloseTime, 0, 5)));

        $marketCloseToday = $now->copy()->setTime($hour, $minute, 0);
        $priceSyncToday = $marketCloseToday->copy()->addMinutes(10);
        $navCalcToday = $marketCloseToday->copy()->addMinutes(20);

        if ($navCalcToday->lessThanOrEqualTo($now)) {
            $marketCloseToday->addDay();
            $priceSyncToday = $marketCloseToday->copy()->addMinutes(10);
            $navCalcToday = $marketCloseToday->copy()->addMinutes(20);
        }

        return [
            'timezone' => $timezone,
            'market_close_time' => $marketCloseTime,
            'next_market_close_at' => $marketCloseToday->toIso8601String(),
            'next_price_sync_at' => $priceSyncToday->toIso8601String(),
            'next_nav_calculation_at' => $navCalcToday->toIso8601String(),
            'auto_calculate_nav' => (bool) $plan->configuration?->auto_calculate_nav,
        ];
    }
}