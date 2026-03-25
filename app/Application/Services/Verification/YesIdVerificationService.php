<?php

namespace App\Application\Services\Verification;

use App\Models\IdentityProviderLog;
use App\Models\InvestorOnboardingSession;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class YesIdVerificationService
{
    public function verifyNida(InvestorOnboardingSession $session, string $nidaNumber): array
    {
        if (blank($nidaNumber)) {
            throw ValidationException::withMessages([
                'nida_number' => ['NIDA number is required.'],
            ]);
        }

        // Mock response for now. Replace with real YesID integration later.
        $response = [
            'success' => true,
            'provider_reference' => 'YESID-' . Str::upper(Str::random(12)),
            'full_name' => 'Mock Verified Investor',
            'first_name' => 'Mock',
            'last_name' => 'Investor',
            'date_of_birth' => '1995-01-15',
            'nationality' => 'Tanzanian',
        ];

        IdentityProviderLog::create([
            'uuid' => (string) Str::uuid(),
            'provider' => 'yesid',
            'request_type' => 'nida_verification',
            'reference' => $response['provider_reference'],
            'onboarding_session_id' => $session->id,
            'request_payload' => [
                'nida_number' => $nidaNumber,
            ],
            'response_payload' => $response,
            'status' => 'verified',
        ]);

        return $response;
    }
}