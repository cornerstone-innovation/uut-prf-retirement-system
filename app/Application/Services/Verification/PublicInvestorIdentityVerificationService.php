<?php

namespace App\Application\Services\Verification;

use App\Models\Investor;
use App\Models\IdentityVerification;
use App\Models\InvestorOnboardingSession;
use Illuminate\Support\Str;

class PublicInvestorIdentityVerificationService
{
    public function createFromOnboardingSession(
        Investor $investor,
        InvestorOnboardingSession $session,
        ?string $providerReference = null
    ): ?IdentityVerification {
        if (! $session->nida_verified_at) {
            return null;
        }

        $existing = IdentityVerification::query()
            ->where('entity_type', 'investor')
            ->where('entity_id', $investor->id)
            ->where('verification_type', 'national_id')
            ->where('status', 'verified')
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $prefill = $session->prefill_data ?? [];

        return IdentityVerification::create([
            'uuid' => (string) Str::uuid(),
            'entity_type' => 'investor',
            'entity_id' => $investor->id,
            'provider' => 'yesid',
            'verification_type' => 'national_id',
            'status' => 'verified',
            'provider_reference' => $providerReference,
            'score' => null,
            'failure_reason' => null,
            'request_payload' => [
                'nida_number' => $session->nida_number,
            ],
            'response_payload' => [
                'full_name' => $prefill['full_name'] ?? null,
                'first_name' => $prefill['first_name'] ?? null,
                'last_name' => $prefill['last_name'] ?? null,
                'date_of_birth' => $prefill['date_of_birth'] ?? null,
                'nationality' => $prefill['nationality'] ?? null,
            ],
            'verified_at' => $session->nida_verified_at,
            'metadata' => [
                'source' => 'public_onboarding',
                'session_uuid' => $session->uuid,
            ],
        ]);
    }
}