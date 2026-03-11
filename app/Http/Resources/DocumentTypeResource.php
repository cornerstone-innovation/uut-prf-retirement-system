<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'allowed_extensions' => $this->allowed_extensions,
            'max_file_size_kb' => $this->max_file_size_kb,
            'requires_expiry_date' => $this->requires_expiry_date,
            'requires_issue_date' => $this->requires_issue_date,
            'requires_document_number' => $this->requires_document_number,
            'requires_manual_verification' => $this->requires_manual_verification,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}