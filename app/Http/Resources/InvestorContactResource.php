<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'email' => $this->email,
            'phone_primary' => $this->phone_primary,
            'phone_secondary' => $this->phone_secondary,
            'alternate_contact_name' => $this->alternate_contact_name,
            'alternate_contact_phone' => $this->alternate_contact_phone,
            'preferred_contact_method' => $this->preferred_contact_method,
        ];
    }
}