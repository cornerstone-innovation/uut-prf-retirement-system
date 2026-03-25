<?php

namespace App\Application\Services\Onboarding;

use App\Models\Investor;
use App\Models\InvestorOnboardingSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Application\Actions\Investor\CreateInvestorAction;
use App\Application\DTOs\Investor\CreateInvestorData;
use App\Application\DTOs\Investor\InvestorAddressData;
use App\Application\DTOs\Investor\InvestorNomineeData;
use App\Application\Services\Investor\InvestorOnboardingValidator;
use App\Application\Services\Auth\InvestorAccountProvisioningService;

class InvestorOnboardingService
{
    public function __construct(
        private readonly InvestorOnboardingValidator $validator,
        private readonly CreateInvestorAction $createInvestorAction,
        private readonly InvestorAccountProvisioningService $accountProvisioningService,
    ) {
    }

    public function start(array $payload): InvestorOnboardingSession
    {
        return InvestorOnboardingSession::create([
            'uuid' => (string) Str::uuid(),
            'investor_type' => $payload['investor_type'],
            'phone_number' => $payload['phone_number'],
            'nida_number' => $payload['nida_number'] ?? null,
            'current_step' => 'started',
            'status' => 'active',
            'payload_snapshot' => $payload,
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function findActiveSessionByUuid(string $uuid): InvestorOnboardingSession
    {
        $session = InvestorOnboardingSession::query()
            ->where('uuid', $uuid)
            ->where('status', 'active')
            ->first();

        if (! $session) {
            throw ValidationException::withMessages([
                'session_id' => ['Onboarding session was not found or is no longer active.'],
            ]);
        }

        if ($session->expires_at && now()->greaterThan($session->expires_at)) {
            $session->update(['status' => 'expired']);

            throw ValidationException::withMessages([
                'session_id' => ['Onboarding session has expired.'],
            ]);
        }

        return $session;
    }

    public function complete(InvestorOnboardingSession $session, array $payload): array
    {
        return DB::transaction(function () use ($session, $payload) {
            $hasPhoneVerified = ! is_null($session->phone_verified_at);
            $hasNidaVerified = ! is_null($session->nida_verified_at);

            if (! $hasPhoneVerified && ! $hasNidaVerified) {
                throw ValidationException::withMessages([
                    'verification' => ['You must verify phone OTP or NIDA before completing registration.'],
                ]);
            }

            $validatedData = [
                'investor_type' => $session->investor_type,
                'full_name' => $payload['full_name'],
                'first_name' => $payload['first_name'] ?? null,
                'middle_name' => $payload['middle_name'] ?? null,
                'last_name' => $payload['last_name'] ?? null,
                'company_name' => $payload['company_name'] ?? null,
                'date_of_birth' => $payload['date_of_birth'] ?? null,
                'gender' => $payload['gender'] ?? null,
                'nationality' => $payload['nationality'] ?? null,
                'national_id_number' => $payload['national_id_number'] ?? ($session->nida_number ?? null),
                'tax_identification_number' => $payload['tax_identification_number'] ?? null,
                'risk_profile' => $payload['risk_profile'] ?? null,
                'occupation' => $payload['occupation'] ?? null,
                'employer_name' => $payload['employer_name'] ?? null,
                'source_of_funds' => $payload['source_of_funds'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'email' => $payload['email'],
                'phone_primary' => $payload['phone_primary'],
                'phone_secondary' => $payload['phone_secondary'] ?? null,
                'alternate_contact_name' => $payload['alternate_contact_name'] ?? null,
                'alternate_contact_phone' => $payload['alternate_contact_phone'] ?? null,
                'preferred_contact_method' => $payload['preferred_contact_method'] ?? null,
                'addresses' => $payload['addresses'],
                'nominees' => $payload['nominees'] ?? [],
            ];

            $this->validator->validate($validatedData);

            $investor = $this->createInvestorAction->execute(
                new CreateInvestorData(
                    investorType: $validatedData['investor_type'],
                    fullName: $validatedData['full_name'],
                    firstName: $validatedData['first_name'],
                    middleName: $validatedData['middle_name'],
                    lastName: $validatedData['last_name'],
                    companyName: $validatedData['company_name'],
                    dateOfBirth: $validatedData['date_of_birth'],
                    gender: $validatedData['gender'],
                    nationality: $validatedData['nationality'],
                    nationalIdNumber: $validatedData['national_id_number'],
                    taxIdentificationNumber: $validatedData['tax_identification_number'],
                    riskProfile: $validatedData['risk_profile'],
                    occupation: $validatedData['occupation'],
                    employerName: $validatedData['employer_name'],
                    sourceOfFunds: $validatedData['source_of_funds'],
                    notes: $validatedData['notes'],
                    email: $validatedData['email'],
                    phonePrimary: $validatedData['phone_primary'],
                    phoneSecondary: $validatedData['phone_secondary'],
                    alternateContactName: $validatedData['alternate_contact_name'],
                    alternateContactPhone: $validatedData['alternate_contact_phone'],
                    preferredContactMethod: $validatedData['preferred_contact_method'],
                    addresses: array_map(
                        fn (array $address) => new InvestorAddressData(
                            addressType: $address['address_type'],
                            country: $address['country'],
                            region: $address['region'] ?? null,
                            city: $address['city'] ?? null,
                            district: $address['district'] ?? null,
                            ward: $address['ward'] ?? null,
                            street: $address['street'] ?? null,
                            postalAddress: $address['postal_address'] ?? null,
                            postalCode: $address['postal_code'] ?? null,
                            isPrimary: (bool) $address['is_primary'],
                        ),
                        $validatedData['addresses']
                    ),
                    nominees: array_map(
                        fn (array $nominee) => new InvestorNomineeData(
                            fullName: $nominee['full_name'],
                            relationship: $nominee['relationship'],
                            dateOfBirth: $nominee['date_of_birth'] ?? null,
                            phone: $nominee['phone'] ?? null,
                            email: $nominee['email'] ?? null,
                            nationalIdNumber: $nominee['national_id_number'] ?? null,
                            allocationPercentage: (float) $nominee['allocation_percentage'],
                            isMinor: (bool) $nominee['is_minor'],
                            guardianName: $nominee['guardian_name'] ?? null,
                            guardianPhone: $nominee['guardian_phone'] ?? null,
                            address: $nominee['address'] ?? null,
                        ),
                        $validatedData['nominees']
                    ),
                    createdBy: null,
                    updatedBy: null,
                )
            );

            $user = $this->accountProvisioningService->createInvestorUser(
                investor: $investor,
                email: $validatedData['email'],
                phone: $validatedData['phone_primary'],
                password: $payload['password'],
            );

            // Seed some initial KYC signals into the profile
            if ($investor->kycProfile) {
                $investor->kycProfile->update([
                    'identity_verification_status' => $hasNidaVerified ? 'verified' : 'pending',
                ]);
            }

            $session->update([
                'status' => 'completed',
                'current_step' => 'completed',
                'payload_snapshot' => array_merge($session->payload_snapshot ?? [], [
                    'investor_id' => $investor->id,
                    'user_id' => $user->id,
                ]),
            ]);

            return [
                'investor' => $investor->fresh(['contact', 'addresses', 'nominees', 'kycProfile']),
                'user' => $user,
            ];
        });
    }
}