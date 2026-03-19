<?php

namespace App\Application\Services\Nav;

use App\Models\Plan;

class IndicativeNavService
{
    public function getIndicativeNav(Plan $plan): array
    {
        // Temporary placeholder until the real NAV engine is built.
        // You can later replace this with latest published NAV lookup.

        $defaultNav = match ($plan->code) {
            'UUT-YOUNGSTERS' => 100.00,
            'UUT-MIDDLE-AGERS' => 100.00,
            'UUT-SENIORS' => 100.00,
            default => 100.00,
        };

        return [
            'nav' => $defaultNav,
            'pricing_date' => now()->toDateString(),
            'pricing_basis' => 'indicative',
            'message' => 'Final units will be allocated at the applicable dealing-day NAV.',
        ];
    }
}