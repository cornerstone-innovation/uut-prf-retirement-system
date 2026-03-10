<?php

namespace App\Application\DTOs\Investor;

class CreateInvestorData
{
    /**
     * @param InvestorAddressData[] $addresses
     * @param InvestorNomineeData[] $nominees
     */
    public function __construct(
        public string $investorType,
        public string $fullName,
        public ?string $firstName = null,
        public ?string $middleName = null,
        public ?string $lastName = null,
        public ?string $companyName = null,
        public ?string $dateOfBirth = null,
        public ?string $gender = null,
        public ?string $nationality = null,
        public ?string $nationalIdNumber = null,
        public ?string $taxIdentificationNumber = null,
        public ?string $riskProfile = null,
        public ?string $occupation = null,
        public ?string $employerName = null,
        public ?string $sourceOfFunds = null,
        public ?string $notes = null,

        public ?string $email = null,
        public ?string $phonePrimary = null,
        public ?string $phoneSecondary = null,
        public ?string $alternateContactName = null,
        public ?string $alternateContactPhone = null,
        public ?string $preferredContactMethod = null,

        public array $addresses = [],
        public array $nominees = [],

        public string $onboardingStatus = 'draft',
        public string $kycStatus = 'pending',
        public string $investorStatus = 'inactive',
        public ?int $createdBy = null,
        public ?int $updatedBy = null,
    ) {
    }
}