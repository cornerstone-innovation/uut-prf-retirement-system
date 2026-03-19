<?php

namespace App\Application\Services\Verification;

use App\Models\Investor;
use App\Models\IdentityVerification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Application\Services\Kyc\KycCompletenessService;

class InvestorVerificationService
{
    public function __construct(
        private readonly SmileIdService $smileIdService,
        private readonly KycCompletenessService $kycCompletenessService
    ) {
    }

    public function verifyNationalId(Investor $investor): array
    {
        return DB::transaction(function () use ($investor) {
            $verification = IdentityVerification::create([
                'uuid' => (string) Str::uuid(),
                'entity_type' => 'investor',
                'entity_id' => $investor->id,
                'provider' => 'smile_id',
                'verification_type' => 'national_id_lookup',
                'status' => 'pending',
                'request_payload' => [
                    'full_name' => $investor->full_name,
                    'national_id_number' => $investor->national_id_number,
                    'country' => 'TZ',
                ],
            ]);

            $result = $this->smileIdService->verifyInvestorNationalId([
                'full_name' => $investor->full_name,
                'national_id_number' => $investor->national_id_number,
                'country' => 'TZ',
            ]);

            $verification->update([
                'provider_reference' => $result['provider_reference'] ?? null,
                'status' => $result['status'],
                'score' => $result['score'] ?? null,
                'failure_reason' => $result['failure_reason'] ?? null,
                'request_payload' => $result['request_payload'] ?? null,
                'response_payload' => $result['response_payload'] ?? null,
                'verified_at' => $result['status'] === 'verified' ? now() : null,
            ]);

           if ($investor->kycProfile) {
                $investor->kycProfile->update([
                    'identity_verification_status' => $result['status'] === 'verified' ? 'verified' : 'rejected',
                    'identity_verified_at' => $result['status'] === 'verified' ? now() : null,
                ]);
            }

            $freshInvestor = $investor->fresh([
                'investorCategory.documentRequirements.documentType',
                'documents.documentType',
                'directors',
                'kycProfile',
            ]);

            $kycSummary = $this->kycCompletenessService->evaluate($freshInvestor);

            return [
                'verification' => $verification->fresh(),
                'investor' => $freshInvestor,
                'kyc_summary' => $kycSummary,
            ];
        });
    }
}