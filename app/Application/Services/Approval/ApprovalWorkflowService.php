<?php

namespace App\Application\Services\Approval;

use App\Models\ApprovalRequest;
use Illuminate\Support\Str;

class ApprovalWorkflowService
{
    public function submit(
        string $approvalType,
        string $entityType,
        int $entityId,
        ?string $entityReference = null,
        ?int $submittedBy = null,
        ?int $currentApproverId = null,
        ?array $metadata = null,
        ?string $comments = null
    ): ApprovalRequest {
        $approvalRequest = ApprovalRequest::create([
            'uuid' => (string) Str::uuid(),
            'approval_type' => $approvalType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_reference' => $entityReference,
            'status' => 'pending',
            'submitted_by' => $submittedBy,
            'current_approver_id' => $currentApproverId,
            'submitted_at' => now(),
            'metadata' => $metadata,
        ]);

        $approvalRequest->actions()->create([
            'action' => 'submitted',
            'acted_by' => $submittedBy,
            'comments' => $comments ?? 'Submitted for approval.',
            'metadata' => $metadata,
        ]);

        return $approvalRequest->load('actions', 'submitter', 'currentApprover');
    }
}