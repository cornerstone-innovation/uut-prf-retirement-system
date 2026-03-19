<?php

namespace App\Application\Services\Document;

use App\Models\InvestorDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Application\Services\Kyc\KycCompletenessService;

class InvestorDocumentVerificationService
{
    public function __construct(
        private readonly KycCompletenessService $kycCompletenessService
    ) {
    }

    public function verify(
        InvestorDocument $document,
        int $reviewedBy,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($document, $reviewedBy, $notes) {
            $this->ensureDecidable($document);

            $document->update([
                'verification_status' => 'verified',
                'verification_notes' => $notes,
                'verified_by' => $reviewedBy,
                'verified_at' => now(),
            ]);

            $freshInvestor = $document->investor->fresh([
                'investorCategory.documentRequirements.documentType',
                'documents.documentType',
                'directors',
                'kycProfile',
            ]);

            $kycSummary = $this->kycCompletenessService->evaluate($freshInvestor);

            return [
                'document' => $document->fresh(['documentType', 'investor']),
                'kyc_summary' => $kycSummary,
            ];
        });
    }

    public function reject(
        InvestorDocument $document,
        int $reviewedBy,
        string $notes
    ): array {
        return DB::transaction(function () use ($document, $reviewedBy, $notes) {
            $this->ensureDecidable($document);

            $document->update([
                'verification_status' => 'rejected',
                'verification_notes' => $notes,
                'verified_by' => $reviewedBy,
                'verified_at' => now(),
            ]);

            $freshInvestor = $document->investor->fresh([
                'investorCategory.documentRequirements.documentType',
                'documents.documentType',
                'directors',
                'kycProfile',
            ]);

            $kycSummary = $this->kycCompletenessService->evaluate($freshInvestor);

            return [
                'document' => $document->fresh(['documentType', 'investor']),
                'kyc_summary' => $kycSummary,
            ];
        });
    }

    protected function ensureDecidable(InvestorDocument $document): void
    {
        if (! in_array($document->verification_status, ['pending', 'under_review'], true)) {
            throw ValidationException::withMessages([
                'document' => ['Only pending or under-review documents can be decided.'],
            ]);
        }
    }
}