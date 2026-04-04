<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'investor_id' => $this->investor_id,
            'investor' => $this->investor ? [
                'id' => $this->investor->id,
                'investor_number' => $this->investor->investor_number,
                'full_name' => $this->investor->full_name,
            ] : null,
            'roles' => $this->getRoleNames()->values(),
            'permissions' => $this->getDirectPermissions()->pluck('name')->values(),
            'all_permissions' => $this->getAllPermissions()->pluck('name')->values(),
            'email_verified_at' => optional($this->email_verified_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}