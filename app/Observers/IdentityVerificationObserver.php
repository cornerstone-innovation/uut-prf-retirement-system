<?php

namespace App\Observers;

use App\Models\IdentityVerification;
use App\Application\Services\Kyc\KycSyncTriggerService;

class IdentityVerificationObserver
{
    public function created(IdentityVerification $identityVerification): void
    {
        app(KycSyncTriggerService::class)->syncForIdentityVerification($identityVerification);
    }

    public function updated(IdentityVerification $identityVerification): void
    {
        if ($identityVerification->wasChanged([
            'status',
            'score',
            'failure_reason',
            'verified_at',
            'reviewed_at',
            'reviewed_by',
        ])) {
            app(KycSyncTriggerService::class)->syncForIdentityVerification($identityVerification);
        }
    }
}