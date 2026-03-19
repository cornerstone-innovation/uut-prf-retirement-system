<?php

namespace App\Application\Services\Kyc;

use App\Models\Investor;
use App\Models\InvestorDocument;
use App\Models\CompanyDirector;
use App\Models\IdentityVerification;

class KycSyncTriggerService
{
    public function syncForInvestor(Investor $investor): array
    {
        return app(KycCompletenessService::class)->syncStatuses($investor);
    }

    public function syncForDocument(InvestorDocument $document): array
    {
        return $this->syncForInvestor($document->investor);
    }

    public function syncForDirector(CompanyDirector $director): array
    {
        return $this->syncForInvestor($director->investor);
    }

    public function syncForIdentityVerification(IdentityVerification $verification): ?array
    {
        if ($verification->entity_type === 'investor') {
            $investor = Investor::find($verification->entity_id);

            return $investor ? $this->syncForInvestor($investor) : null;
        }

        if ($verification->entity_type === 'company_director') {
            $director = CompanyDirector::find($verification->entity_id);

            return $director ? $this->syncForInvestor($director->investor) : null;
        }

        return null;
    }
}