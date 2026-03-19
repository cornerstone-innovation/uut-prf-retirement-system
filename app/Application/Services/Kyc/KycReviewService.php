<?php

namespace App\Application\Services\Kyc;

use App\Models\Investor;
use App\Models\KycReview;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KycReviewService
{
    public function __construct(
        private readonly KycCompletenessService $kycCompletenessService
    ) {
    }

    public function review(
        Investor $investor,
        int $reviewedBy,
        string $decision,
        ?string $reviewNotes = null,
        ?string $escalationReason = null,
        ?string $overrideReason = null
    ): array {
        return DB::transaction(function () use (
            $investor,
            $reviewedBy,
            $decision,
            $reviewNotes,
            $escalationReason,
            $overrideReason
        ) {
            $kycSummary = $this->kycCompletenessService->evaluate($investor->fresh([
                'investorCategory.documentRequirements.documentType',
                'documents.documentType',
                'directors',
                'kycProfile',
            ]));

            if ($decision === 'approved' && ! $kycSummary['is_kyc_complete']) {
                throw ValidationException::withMessages([
                    'decision' => ['KYC cannot be approved until all required checks are complete.'],
                ]);
            }

            $review = KycReview::create([
                'uuid' => (string) Str::uuid(),
                'investor_id' => $investor->id,
                'review_status' => $decision === 'escalated' ? 'escalated' : 'completed',
                'decision' => $decision,
                'review_notes' => $reviewNotes,
                'escalation_reason' => $escalationReason,
                'override_reason' => $overrideReason,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
                'metadata' => [
                    'kyc_summary_snapshot' => $kycSummary,
                ],
            ]);

            $onboardingStatus = $investor->onboarding_status;
            $kycStatus = $investor->kyc_status;
            $investorStatus = $investor->investor_status;

            if ($decision === 'approved') {
                $onboardingStatus = 'approved';
                $kycStatus = 'approved';
                $investorStatus = 'active';
            } elseif ($decision === 'rejected') {
                $onboardingStatus = 'rejected';
                $kycStatus = 'rejected';
                $investorStatus = 'inactive';
            } elseif ($decision === 'escalated') {
                $onboardingStatus = 'kyc_under_review';
                $kycStatus = 'under_review';
                $investorStatus = 'inactive';
            }

            $investor->update([
                'onboarding_status' => $onboardingStatus,
                'kyc_status' => $kycStatus,
                'investor_status' => $investorStatus,
            ]);

            return [
                'review' => $review->fresh(),
                'investor' => $investor->fresh(['kycProfile']),
                'kyc_summary' => $this->kycCompletenessService->evaluate($investor->fresh([
                    'investorCategory.documentRequirements.documentType',
                    'documents.documentType',
                    'directors',
                    'kycProfile',
                ])),
            ];
        });
    }
}