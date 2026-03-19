<?php

namespace App\Application\Services\Verification;

use App\Models\CompanyDirector;
use App\Models\IdentityVerification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Application\Services\Kyc\KycCompletenessService;

class DirectorVerificationService
{
    public function __construct(
        private readonly SmileIdService $smileIdService,
        private readonly KycCompletenessService $kycCompletenessService
    ) {
    }

    public function verifyNationalId(CompanyDirector $director): array
    {
        return DB::transaction(function () use ($director) {
            $verification = IdentityVerification::create([
                'uuid' => (string) Str::uuid(),
                'entity_type' => 'company_director',
                'entity_id' => $director->id,
                'provider' => 'smile_id',
                'verification_type' => 'national_id_lookup',
                'status' => 'pending',
                'request_payload' => [
                    'full_name' => $director->full_name,
                    'national_id_number' => $director->national_id_number,
                    'country' => 'TZ',
                ],
            ]);

            $result = $this->smileIdService->verifyDirectorNationalId([
                'full_name' => $director->full_name,
                'national_id_number' => $director->national_id_number,
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

            $director->update([
                'identity_verification_status' => $result['status'] === 'verified' ? 'verified' : 'rejected',
                'smile_verification_id' => $result['provider_reference'] ?? null,
                'verified_at' => $result['status'] === 'verified' ? now() : null,
            ]);

            $freshDirector = $director->fresh(['investor']);
            $freshVerification = $verification->fresh();

            $kycSummary = $this->kycCompletenessService->evaluate(
                $freshDirector->investor->fresh([
                    'investorCategory.documentRequirements.documentType',
                    'documents.documentType',
                    'directors',
                    'kycProfile',
                ])
            );

            return [
                'director' => $freshDirector,
                'verification' => $freshVerification,
                'kyc_summary' => $kycSummary,
            ];
        });
    }
}