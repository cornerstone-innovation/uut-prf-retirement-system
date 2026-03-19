<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KycCaseAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'investor_id' => $this->investor_id,
            'assigned_to' => $this->assigned_to,
            'assigned_by' => $this->assigned_by,
            'status' => $this->status,
            'assignment_notes' => $this->assignment_notes,
            'assigned_at' => optional($this->assigned_at)?->toDateTimeString(),
            'ended_at' => optional($this->ended_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}