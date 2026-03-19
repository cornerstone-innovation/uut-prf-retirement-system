<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'purchase_request_id' => $this->purchase_request_id,
            'investor_id' => $this->investor_id,
            'provider' => $this->provider,
            'reference' => $this->reference,
            'provider_reference' => $this->provider_reference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'paid_at' => optional($this->paid_at)?->toDateTimeString(),
            'metadata' => $this->metadata,
            'purchase_request' => $this->whenLoaded('purchaseRequest', function () {
                return [
                    'id' => $this->purchaseRequest?->id,
                    'uuid' => $this->purchaseRequest?->uuid,
                    'status' => $this->purchaseRequest?->status,
                ];
            }),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}