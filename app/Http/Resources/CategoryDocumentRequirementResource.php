<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryDocumentRequirementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_required' => $this->is_required,
            'is_multiple_allowed' => $this->is_multiple_allowed,
            'minimum_required_count' => $this->minimum_required_count,
            'requires_verification' => $this->requires_verification,
            'is_visible_on_onboarding' => $this->is_visible_on_onboarding,
            'applies_to_resident' => $this->applies_to_resident,
            'applies_to_non_resident' => $this->applies_to_non_resident,
            'applies_to_minor' => $this->applies_to_minor,
            'applies_to_joint' => $this->applies_to_joint,
            'applies_to_corporate' => $this->applies_to_corporate,
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'document_type' => new DocumentTypeResource($this->whenLoaded('documentType')),
        ];
    }
}