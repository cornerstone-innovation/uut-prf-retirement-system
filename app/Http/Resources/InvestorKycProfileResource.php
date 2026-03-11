<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorKycProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kyc_reference' => $this->kyc_reference,
            'document_status' => $this->document_status,
            'identity_verification_status' => $this->identity_verification_status,
            'address_verification_status' => $this->address_verification_status,
            'tax_verification_status' => $this->tax_verification_status,
            'pep_check_status' => $this->pep_check_status,
            'sanctions_check_status' => $this->sanctions_check_status,
            'aml_risk_level' => $this->aml_risk_level,
            'review_notes' => $this->review_notes,
            'submitted_at' => optional($this->submitted_at)?->toDateTimeString(),
            'reviewed_at' => optional($this->reviewed_at)?->toDateTimeString(),
            'approved_at' => optional($this->approved_at)?->toDateTimeString(),
            'rejected_at' => optional($this->rejected_at)?->toDateTimeString(),
            'expires_at' => optional($this->expires_at)?->toDateTimeString(),
        ];
    }
}