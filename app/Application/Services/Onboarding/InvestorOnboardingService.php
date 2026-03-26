<?php

namespace App\Application\Services\Onboarding;

use App\Models\Investor;
use App\Models\InvestorCategory;
use App\Models\InvestorOnboardingSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Application\Services\Kyc\KycCompletenessService;
use App\Application\Services\Auth\InvestorAccountProvisioningService;
use App\Application\Services\Verification\PublicInvestorIdentityVerificationService;

class InvestorOnboardingService
{
    public function __construct(
        private readonly InvestorAccountProvisioningService $accountProvisioningService,
        private readonly KycCompletenessService $kycCompletenessService,
        private readonly PublicInvestorIdentityVerificationService $publicInvestorIdentityVerificationService
    ) {
    }

    public function start(string $investorType, string $phoneNumber, ?string $nidaNumber = null): InvestorOnboardingSession
    {
        return InvestorOnboardingSession::create([
            'uuid' => (string) Str::uuid(),
            'investor_type' => $investorType,
            'phone_number' => $phoneNumber,
            'nida_number' => $nidaNumber,
            'current_step' => 'started',
            'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);
    }

    public function markPhoneVerified(InvestorOnboardingSession $session): InvestorOnboardingSession
    {
        $session->update([
            'phone_verified_at' => now(),
            'current_step' => 'phone_verified',
        ]);

        return $session->fresh();
    }

    public function markNidaVerified(
        InvestorOnboardingSession $session,
        array $prefillData,
        string $nidaNumber
    ): InvestorOnboardingSession {
        $existingPrefill = $session->prefill_data ?? [];

        $session->update([
            'nida_number' => $nidaNumber,
            'nida_verified_at' => now(),
            'prefill_data' => array_merge($existingPrefill, $prefillData),
            'current_step' => 'nida_verified',
        ]);

        return $session->fresh();
    }

public function complete(InvestorOnboardingSession $session, array $data): array
{
    if (! $session->phone_verified_at && ! $session->nida_verified_at) {
        throw ValidationException::withMessages([
            'verification' => ['Either phone OTP verification or NIDA verification is required before registration can be completed.'],
        ]);
    }

    return DB::transaction(function () use ($session, $data) {
        $investorCategory = $this->resolveInvestorCategory($data['investor_type']);

        $investor = Investor::create([
            'uuid' => (string) Str::uuid(),
            'investor_number' => $this->generateInvestorNumber(),
            'investor_type' => $data['investor_type'],
            'investor_category_id' => $investorCategory?->id,
            'first_name' => $data['first_name'] ?? null,
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'full_name' => $data['full_name'],
            'company_name' => $data['company_name'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'national_id_number' => $data['national_id_number'] ?? $session->nida_number,
            'tax_identification_number' => $data['tax_identification_number'] ?? null,
            'onboarding_status' => 'draft',
            'kyc_status' => 'pending',
            'investor_status' => 'inactive',
            'risk_profile' => $data['risk_profile'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'employer_name' => $data['employer_name'] ?? null,
            'source_of_funds' => $data['source_of_funds'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $investor->contact()->create([
            'email' => $data['email'],
            'phone_primary' => $data['phone_primary'],
            'phone_secondary' => $data['phone_secondary'] ?? null,
            'alternate_contact_name' => $data['alternate_contact_name'] ?? null,
            'alternate_contact_phone' => $data['alternate_contact_phone'] ?? null,
            'preferred_contact_method' => $data['preferred_contact_method'] ?? null,
        ]);

        foreach ($data['addresses'] as $address) {
            $investor->addresses()->create($address);
        }

        if (! empty($data['nominees']) && is_array($data['nominees'])) {
            foreach ($data['nominees'] as $nominee) {
                $investor->nominees()->create($nominee);
            }
        }

        $investor->kycProfile()->create([
            'kyc_reference' => $this->generateKycReference(),
            'kyc_tier' => 'tier_0',
            'document_status' => 'incomplete',
            'identity_verification_status' => $session->nida_verified_at ? 'verified' : 'pending',
            'address_verification_status' => 'pending',
            'tax_verification_status' => 'pending',
        ]);

        $this->publicInvestorIdentityVerificationService->createFromOnboardingSession(
            investor: $investor,
            session: $session,
            providerReference: null
        );

        $user = $this->accountProvisioningService->createUserForInvestor(
            investor: $investor,
            name: $data['full_name'],
            email: $data['email'],
            phone: $data['phone_primary'],
            password: $data['password'],
        );

        $session->update([
            'status' => 'completed',
            'current_step' => 'completed',
            'payload_snapshot' => $data,
            'metadata' => array_merge($session->metadata ?? [], [
                'investor_id' => $investor->id,
                'user_id' => $user->id,
            ]),
        ]);

        $this->kycCompletenessService->syncStatuses($investor);

        $investor = $investor->fresh(['contact', 'addresses', 'nominees', 'kycProfile']);

        $token = $user->createToken('investor-portal')->plainTextToken;

        return [
            'investor' => $investor,
            'user' => $user,
            'token' => $token,
        ];
    });
}

    protected function resolveInvestorCategory(string $investorType): ?InvestorCategory
    {
        $categoryCode = match ($investorType) {
            'individual' => 'individual',
            'corporate' => 'corporate',
            default => 'individual',
        };

        return InvestorCategory::query()
            ->where('code', $categoryCode)
            ->where('is_active', true)
            ->first();
    }

    protected function generateInvestorNumber(): string
    {
        $nextId = (Investor::max('id') ?? 0) + 1;
        return 'INV-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }

    protected function generateKycReference(): string
    {
        $nextId = (Investor::count() ?? 0) + 1;
        return 'KYC-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }
}