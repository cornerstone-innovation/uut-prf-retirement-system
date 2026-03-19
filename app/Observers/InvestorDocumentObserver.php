<?php

namespace App\Observers;

use App\Models\InvestorDocument;
use App\Application\Services\Kyc\KycSyncTriggerService;

class InvestorDocumentObserver
{
    public function created(InvestorDocument $investorDocument): void
    {
        app(KycSyncTriggerService::class)->syncForDocument($investorDocument);
    }

    public function updated(InvestorDocument $investorDocument): void
    {
        if ($investorDocument->wasChanged([
            'verification_status',
            'verification_notes',
            'verified_by',
            'verified_at',
            'expiry_date',
        ])) {
            app(KycSyncTriggerService::class)->syncForDocument($investorDocument);
        }
    }

    public function deleted(InvestorDocument $investorDocument): void
    {
        if ($investorDocument->investor) {
            app(KycSyncTriggerService::class)->syncForInvestor($investorDocument->investor);
        }
    }
}