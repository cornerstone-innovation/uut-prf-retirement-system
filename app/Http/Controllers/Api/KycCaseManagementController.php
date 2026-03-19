<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kyc\AssignKycCaseRequest;
use App\Http\Requests\Kyc\StoreKycCaseNoteRequest;
use App\Http\Resources\KycCaseAssignmentResource;
use App\Http\Resources\KycCaseNoteResource;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Services\Kyc\KycCaseManagementService;

class KycCaseManagementController extends Controller
{
    public function assignments(Investor $investor): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('review kyc') ||
            auth()->user()?->can('view approvals'),
            403
        );

        $assignments = $investor->kycCaseAssignments()->latest('id')->get();

        return response()->json([
            'message' => 'KYC case assignments retrieved successfully.',
            'data' => KycCaseAssignmentResource::collection($assignments),
        ]);
    }

    public function assign(
        AssignKycCaseRequest $request,
        Investor $investor,
        KycCaseManagementService $service,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('review kyc') ||
            auth()->user()?->can('escalate kyc'),
            403
        );

        $assignment = $service->assign(
            investor: $investor,
            assignedTo: (int) $request->input('assigned_to'),
            assignedBy: (int) $request->user()->id,
            notes: $request->input('assignment_notes')
        );

        $auditLogger->log(
            userId: $request->user()->id,
            action: 'investor.kyc_case_assigned',
            entityType: 'investor',
            entityId: $investor->id,
            entityReference: $investor->investor_number,
            metadata: [
                'assignment_id' => $assignment->id,
                'assigned_to' => $assignment->assigned_to,
                'assignment_notes' => $assignment->assignment_notes,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'KYC case assigned successfully.',
            'data' => new KycCaseAssignmentResource($assignment),
        ]);
    }

    public function notes(Investor $investor): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('review kyc') ||
            auth()->user()?->can('view approvals'),
            403
        );

        $notes = $investor->kycCaseNotes()->latest('id')->get();

        return response()->json([
            'message' => 'KYC case notes retrieved successfully.',
            'data' => KycCaseNoteResource::collection($notes),
        ]);
    }

    public function storeNote(
        StoreKycCaseNoteRequest $request,
        Investor $investor,
        KycCaseManagementService $service,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('review kyc') ||
            auth()->user()?->can('escalate kyc'),
            403
        );

        $note = $service->addNote(
            investor: $investor,
            authorId: (int) $request->user()->id,
            note: $request->input('note'),
            noteType: $request->input('note_type', 'internal'),
            isPinned: (bool) $request->boolean('is_pinned', false),
        );

        $auditLogger->log(
            userId: $request->user()->id,
            action: 'investor.kyc_case_note_added',
            entityType: 'investor',
            entityId: $investor->id,
            entityReference: $investor->investor_number,
            metadata: [
                'note_id' => $note->id,
                'note_type' => $note->note_type,
                'is_pinned' => $note->is_pinned,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'KYC case note added successfully.',
            'data' => new KycCaseNoteResource($note),
        ], 201);
    }
}