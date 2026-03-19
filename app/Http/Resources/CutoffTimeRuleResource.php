<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CutoffTimeRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'plan_id' => $this->plan_id,
            'cutoff_time' => $this->cutoff_time,
            'timezone' => $this->timezone,
            'effective_from' => optional($this->effective_from)?->toDateString(),
            'effective_to' => optional($this->effective_to)?->toDateString(),
            'status' => $this->status,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'id' => $this->plan?->id,
                    'code' => $this->plan?->code,
                    'name' => $this->plan?->name,
                ];
            }),
            'approved_by' => $this->approved_by,
            'approved_at' => optional($this->approved_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}