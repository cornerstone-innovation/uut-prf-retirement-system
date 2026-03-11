<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorNomineeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'relationship' => $this->relationship,
            'date_of_birth' => optional($this->date_of_birth)->toDateString(),
            'phone' => $this->phone,
            'email' => $this->email,
            'national_id_number' => $this->national_id_number,
            'allocation_percentage' => $this->allocation_percentage,
            'is_minor' => $this->is_minor,
            'guardian_name' => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'address' => $this->address,
        ];
    }
}