<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'investor_id' => $this->investor_id,
            'investor_kyc_profile_id' => $this->investor_kyc_profile_id,
            'investor_category_id' => $this->investor_category_id,
            'parent_document_id' => $this->parent_document_id,
            'version_number' => $this->version_number,
            'is_current_version' => $this->is_current_version,
            'document_type_id' => $this->document_type_id,
            'original_filename' => $this->original_filename,
            'stored_filename' => $this->stored_filename,
            'storage_disk' => $this->storage_disk,
            'storage_path' => $this->storage_path,
            'mime_type' => $this->mime_type,
            'file_extension' => $this->file_extension,
            'file_size_bytes' => $this->file_size_bytes,
            'document_number' => $this->document_number,
            'issue_date' => optional($this->issue_date)?->toDateString(),
            'expiry_date' => optional($this->expiry_date)?->toDateString(),
            'verification_status' => $this->verification_status,
            'verification_notes' => $this->verification_notes,
            'verified_by' => $this->verified_by,
            'verified_at' => optional($this->verified_at)?->toDateTimeString(),
            'replaced_at' => optional($this->replaced_at)?->toDateTimeString(),
            'uploaded_by' => $this->uploaded_by,
            'uploaded_at' => optional($this->uploaded_at)?->toDateTimeString(),
            'document_type' => new DocumentTypeResource($this->whenLoaded('documentType')),
        ];
    }
}