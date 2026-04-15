<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NavRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'plan_id' => $this->plan_id,
            'valuation_date' => optional($this->valuation_date)?->toDateString(),
            'nav_per_unit' => $this->nav_per_unit,
            'status' => $this->status,
            'source' => $this->source,
            'notes' => $this->notes,

            'plan' => $this->whenLoaded('plan', function () {
                return [
                    'id' => $this->plan?->id,
                    'code' => $this->plan?->code,
                    'name' => $this->plan?->name,
                ];
            }),

            'approval' => [
                'approved_by_1' => $this->approved_by_1,
                'approved_by_1_name' => $this->whenLoaded('approverOne', function () {
                    return $this->approverOne?->name;
                }),
                'approved_at_1' => optional($this->approved_at_1)?->toDateTimeString(),

                'approved_by_2' => $this->approved_by_2,
                'approved_by_2_name' => $this->whenLoaded('approverTwo', function () {
                    return $this->approverTwo?->name;
                }),
                'approved_at_2' => optional($this->approved_at_2)?->toDateTimeString(),

                'published_by' => $this->published_by,
                'published_by_name' => $this->whenLoaded('publisher', function () {
                    return $this->publisher?->name;
                }),
                'published_at' => optional($this->published_at)?->toDateTimeString(),
            ],

            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}