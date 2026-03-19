<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'investor_id' => $this->investor_id,
            'plan_id' => $this->plan_id,
            'purchase_request_id' => $this->purchase_request_id,
            'transaction_type' => $this->transaction_type,
            'status' => $this->status,
            'gross_amount' => $this->gross_amount,
            'net_amount' => $this->net_amount,
            'units' => $this->units,
            'nav_per_unit' => $this->nav_per_unit,
            'currency' => $this->currency,
            'option' => $this->option,
            'trade_date' => optional($this->trade_date)?->toDateString(),
            'pricing_date' => optional($this->pricing_date)?->toDateString(),
            'processed_at' => optional($this->processed_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}