<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitLotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'investor_id' => $this->investor_id,
            'plan_id' => $this->plan_id,
            'investment_transaction_id' => $this->investment_transaction_id,
            'original_units' => $this->original_units,
            'remaining_units' => $this->remaining_units,
            'nav_per_unit' => $this->nav_per_unit,
            'gross_amount' => $this->gross_amount,
            'acquired_date' => optional($this->acquired_date)?->toDateString(),
            'status' => $this->status,
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}