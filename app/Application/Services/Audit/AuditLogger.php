<?php

namespace App\Application\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $entityReference = null,
        ?array $metadata = null,
        ?Request $request = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_reference' => $entityReference,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}