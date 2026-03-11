<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyDirectorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'investor_id' => $this->investor_id,
            'full_name' => $this->full_name,
            'national_id_number' => $this->national_id_number,
            'passport_number' => $this->passport_number,
            'nationality' => $this->nationality,
            'role' => $this->role,
            'has_signing_authority' => $this->has_signing_authority,
            'ownership_percentage' => $this->ownership_percentage,
            'identity_verification_status' => $this->identity_verification_status,
            'smile_verification_id' => $this->smile_verification_id,
            'verified_at' => optional($this->verified_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}