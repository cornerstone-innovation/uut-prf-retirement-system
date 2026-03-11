<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'address_type' => $this->address_type,
            'country' => $this->country,
            'region' => $this->region,
            'city' => $this->city,
            'district' => $this->district,
            'ward' => $this->ward,
            'street' => $this->street,
            'postal_address' => $this->postal_address,
            'postal_code' => $this->postal_code,
            'is_primary' => $this->is_primary,
        ];
    }
}