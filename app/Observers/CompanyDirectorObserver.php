<?php

namespace App\Observers;

use App\Models\CompanyDirector;
use App\Application\Services\Kyc\KycSyncTriggerService;

class CompanyDirectorObserver
{
    public function created(CompanyDirector $companyDirector): void
    {
        app(KycSyncTriggerService::class)->syncForDirector($companyDirector);
    }

    public function updated(CompanyDirector $companyDirector): void
    {
        if ($companyDirector->wasChanged([
            'has_signing_authority',
            'identity_verification_status',
            'verified_at',
            'smile_verification_id',
        ])) {
            app(KycSyncTriggerService::class)->syncForDirector($companyDirector);
        }
    }

    public function deleted(CompanyDirector $companyDirector): void
    {
        if ($companyDirector->investor) {
            app(KycSyncTriggerService::class)->syncForInvestor($companyDirector->investor);
        }
    }
}