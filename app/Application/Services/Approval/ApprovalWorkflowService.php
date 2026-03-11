<?php

namespace App\Application\Services\Approval;

use App\Models\ApprovalRequest;
use Illuminate\Validation\ValidationException;
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

    public function approve(
        ApprovalRequest $approvalRequest,
        int $actedBy,
        ?string $comments = null,
        ?array $metadata = null
    ): ApprovalRequest {
        $this->ensurePending($approvalRequest);

        $approvalRequest->update([
            'status' => 'approved',
            'decided_at' => now(),
            'decision_reason' => $comments,
        ]);

        $approvalRequest->actions()->create([
            'action' => 'approved',
            'acted_by' => $actedBy,
            'comments' => $comments,
            'metadata' => $metadata,
        ]);

        return $approvalRequest->load('actions', 'submitter', 'currentApprover');
    }

    public function reject(
        ApprovalRequest $approvalRequest,
        int $actedBy,
        ?string $comments = null,
        ?array $metadata = null
    ): ApprovalRequest {
        $this->ensurePending($approvalRequest);

        $approvalRequest->update([
            'status' => 'rejected',
            'decided_at' => now(),
            'decision_reason' => $comments,
        ]);

        $approvalRequest->actions()->create([
            'action' => 'rejected',
            'acted_by' => $actedBy,
            'comments' => $comments,
            'metadata' => $metadata,
        ]);

        return $approvalRequest->load('actions', 'submitter', 'currentApprover');
    }

    private function ensurePending(ApprovalRequest $approvalRequest): void
    {
        if ($approvalRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'approval' => ['Only pending approval requests can be decided.'],
            ]);
        }
    }
}