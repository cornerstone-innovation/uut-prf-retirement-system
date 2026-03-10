<?php

namespace App\Application\DTOs\Investor;

class InvestorAddressData
{
    public function __construct(
        public string $addressType,
        public string $country,
        public ?string $region = null,
        public ?string $city = null,
        public ?string $district = null,
        public ?string $ward = null,
        public ?string $street = null,
        public ?string $postalAddress = null,
        public ?string $postalCode = null,
        public bool $isPrimary = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'address_type' => $this->addressType,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'district' => $this->district,
            'ward' => $this->ward,
            'street' => $this->street,
            'postal_address' => $this->postalAddress,
            'postal_code' => $this->postalCode,
            'is_primary' => $this->isPrimary,
        ];
    }
}