<?php

namespace App\Application\Services\Kyc;

use App\Models\Investor;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\KycCaseNote;
use App\Models\KycCaseAssignment;

class KycCaseManagementService
{
    public function assign(
        Investor $investor,
        int $assignedTo,
        int $assignedBy,
        ?string $notes = null
    ): KycCaseAssignment {
        return DB::transaction(function () use ($investor, $assignedTo, $assignedBy, $notes) {
            $investor->kycCaseAssignments()
                ->where('status', 'active')
                ->update([
                    'status' => 'reassigned',
                    'ended_at' => now(),
                ]);

            return KycCaseAssignment::create([
                'uuid' => (string) Str::uuid(),
                'investor_id' => $investor->id,
                'assigned_to' => $assignedTo,
                'assigned_by' => $assignedBy,
                'status' => 'active',
                'assignment_notes' => $notes,
                'assigned_at' => now(),
            ]);
        });
    }

    public function addNote(
        Investor $investor,
        int $authorId,
        string $note,
        string $noteType = 'internal',
        bool $isPinned = false
    ): KycCaseNote {
        return KycCaseNote::create([
            'uuid' => (string) Str::uuid(),
            'investor_id' => $investor->id,
            'author_id' => $authorId,
            'note' => $note,
            'note_type' => $noteType,
            'is_pinned' => $isPinned,
        ]);
    }
}