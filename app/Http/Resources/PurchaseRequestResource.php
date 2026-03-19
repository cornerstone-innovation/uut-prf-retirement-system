<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'investor_id' => $this->investor_id,
            'plan_id' => $this->plan_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'request_type' => $this->request_type,
            'status' => $this->status,
            'kyc_tier_at_request' => $this->kyc_tier_at_request,
            'is_sip' => $this->is_sip,
            'notes' => $this->notes,
            'submitted_at' => optional($this->submitted_at)?->toDateTimeString(),
            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'id' => $this->plan?->id,
                    'code' => $this->plan?->code,
                    'name' => $this->plan?->name,
                ];
            }),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'option' => $this->option,
            'pricing_date' => optional($this->pricing_date)?->toDateString(),
        ];
    }
}