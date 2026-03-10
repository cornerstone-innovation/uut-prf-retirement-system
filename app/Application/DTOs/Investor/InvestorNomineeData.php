<?php

namespace App\Application\DTOs\Investor;

class InvestorNomineeData
{
    public function __construct(
        public string $fullName,
        public string $relationship,
        public ?string $dateOfBirth = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $nationalIdNumber = null,
        public float $allocationPercentage = 0.00,
        public bool $isMinor = false,
        public ?string $guardianName = null,
        public ?string $guardianPhone = null,
        public ?string $address = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'full_name' => $this->fullName,
            'relationship' => $this->relationship,
            'date_of_birth' => $this->dateOfBirth,
            'phone' => $this->phone,
            'email' => $this->email,
            'national_id_number' => $this->nationalIdNumber,
            'allocation_percentage' => $this->allocationPercentage,
            'is_minor' => $this->isMinor,
            'guardian_name' => $this->guardianName,
            'guardian_phone' => $this->guardianPhone,
            'address' => $this->address,
        ];
    }
}