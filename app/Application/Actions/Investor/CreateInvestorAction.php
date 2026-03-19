<?php

namespace App\Application\Actions\Investor;

use App\Models\Investor;
use App\Models\InvestorCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Application\DTOs\Investor\CreateInvestorData;
use App\Application\Services\Approval\ApprovalWorkflowService;

class CreateInvestorAction
{
    public function __construct(
        private readonly ApprovalWorkflowService $approvalWorkflowService
    ) {
    }

    public function execute(CreateInvestorData $data): Investor
    {
        return DB::transaction(function () use ($data) {
            $investorCategory = $this->resolveInvestorCategory($data);

            $investor = Investor::create([
                'uuid' => (string) Str::uuid(),
                'investor_number' => $this->generateInvestorNumber(),
                'investor_type' => $data->investorType,
                'investor_category_id' => $investorCategory?->id,
                'first_name' => $data->firstName,
                'middle_name' => $data->middleName,
                'last_name' => $data->lastName,
                'full_name' => $data->fullName,
                'company_name' => $data->companyName,
                'date_of_birth' => $data->dateOfBirth,
                'gender' => $data->gender,
                'nationality' => $data->nationality,
                'national_id_number' => $data->nationalIdNumber,
                'tax_identification_number' => $data->taxIdentificationNumber,
                'onboarding_status' => $data->onboardingStatus,
                'kyc_status' => $data->kycStatus,
                'investor_status' => $data->investorStatus,
                'risk_profile' => $data->riskProfile,
                'occupation' => $data->occupation,
                'employer_name' => $data->employerName,
                'source_of_funds' => $data->sourceOfFunds,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
                'updated_by' => $data->updatedBy,
            ]);

            $investor->contact()->create([
                'email' => $data->email,
                'phone_primary' => $data->phonePrimary,
                'phone_secondary' => $data->phoneSecondary,
                'alternate_contact_name' => $data->alternateContactName,
                'alternate_contact_phone' => $data->alternateContactPhone,
                'preferred_contact_method' => $data->preferredContactMethod,
            ]);

            foreach ($data->addresses as $address) {
                $investor->addresses()->create($address->toArray());
            }

            foreach ($data->nominees as $nominee) {
                $investor->nominees()->create($nominee->toArray());
            }

           $investor->kycProfile()->create([
            'kyc_reference' => $this->generateKycReference(),
            'kyc_tier' => 'tier_0',
            'document_status' => 'incomplete',
            'identity_verification_status' => 'pending',
            'address_verification_status' => 'pending',
            'tax_verification_status' => 'pending',
        ]);

            $this->approvalWorkflowService->submit(
                approvalType: 'investor_onboarding',
                entityType: 'investor',
                entityId: $investor->id,
                entityReference: $investor->investor_number,
                submittedBy: $data->createdBy,
                metadata: [
                    'investor_type' => $investor->investor_type,
                    'investor_category_id' => $investor->investor_category_id,
                    'investor_category_code' => $investorCategory?->code,
                    'full_name' => $investor->full_name,
                    'onboarding_status' => $investor->onboarding_status,
                    'kyc_status' => $investor->kyc_status,
                ],
                comments: 'Investor onboarding submitted for approval.'
            );

            return $investor->load('investorCategory', 'contact', 'addresses', 'nominees', 'kycProfile');
        });
    }

    private function resolveInvestorCategory(CreateInvestorData $data): ?InvestorCategory
    {
        $categoryCode = match ($data->investorType) {
            'individual' => 'individual',
            'corporate' => 'corporate',
            default => 'individual',
        };

        return InvestorCategory::query()
            ->where('code', $categoryCode)
            ->where('is_active', true)
            ->first();
    }

    private function generateInvestorNumber(): string
    {
        $nextId = (Investor::max('id') ?? 0) + 1;

        return 'INV-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }

    private function generateKycReference(): string
    {
        $nextId = (Investor::count() ?? 0) + 1;

        return 'KYC-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }
}