<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'requires_guardian' => $this->requires_guardian,
            'requires_custodian' => $this->requires_custodian,
            'requires_ubo' => $this->requires_ubo,
            'requires_authorized_signatories' => $this->requires_authorized_signatories,
            'allows_joint_holding' => $this->allows_joint_holding,
            'is_minor_category' => $this->is_minor_category,
            'sort_order' => $this->sort_order,
            'document_requirements' => CategoryDocumentRequirementResource::collection(
                $this->whenLoaded('documentRequirements')
            ),
        ];
    }
}