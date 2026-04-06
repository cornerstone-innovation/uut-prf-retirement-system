<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roles = method_exists($this->resource, 'getRoleNames')
            ? $this->getRoleNames()->values()->all()
            : [];

        $directPermissions = method_exists($this->resource, 'getDirectPermissions')
            ? $this->getDirectPermissions()->pluck('name')->values()->all()
            : [];

        $allPermissions = method_exists($this->resource, 'getAllPermissions')
            ? $this->getAllPermissions()->pluck('name')->values()->all()
            : [];

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => (bool) $this->is_active,
            'investor_id' => $this->investor_id,
            'investor' => $this->investor ? [
                'id' => $this->investor->id,
                'investor_number' => $this->investor->investor_number ?? null,
                'full_name' => $this->investor->full_name ?? null,
            ] : null,
            'roles' => $roles,
            'permissions' => $directPermissions,
            'all_permissions' => $allPermissions,
            'email_verified_at' => optional($this->email_verified_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}