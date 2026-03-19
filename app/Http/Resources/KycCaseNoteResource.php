<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KycCaseNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'investor_id' => $this->investor_id,
            'author_id' => $this->author_id,
            'note' => $this->note,
            'note_type' => $this->note_type,
            'is_pinned' => $this->is_pinned,
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}