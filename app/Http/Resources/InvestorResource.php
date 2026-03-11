<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'investor_number' => $this->investor_number,
            'investor_type' => $this->investor_type,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'company_name' => $this->company_name,
            'date_of_birth' => optional($this->date_of_birth)->toDateString(),
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'national_id_number' => $this->national_id_number,
            'tax_identification_number' => $this->tax_identification_number,
            'onboarding_status' => $this->onboarding_status,
            'kyc_status' => $this->kyc_status,
            'investor_status' => $this->investor_status,
            'risk_profile' => $this->risk_profile,
            'occupation' => $this->occupation,
            'employer_name' => $this->employer_name,
            'source_of_funds' => $this->source_of_funds,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),

            'contact' => new InvestorContactResource($this->whenLoaded('contact')),
            'addresses' => InvestorAddressResource::collection($this->whenLoaded('addresses')),
            'nominees' => InvestorNomineeResource::collection($this->whenLoaded('nominees')),
            'kyc_profile' => new InvestorKycProfileResource($this->whenLoaded('kycProfile')),
        ];
    }
}