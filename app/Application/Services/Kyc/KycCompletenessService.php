<?php

namespace App\Application\Services\Kyc;

use App\Models\Investor;
use App\Models\IdentityVerification;
use Illuminate\Support\Facades\DB;

class KycCompletenessService
{
    public function evaluate(Investor $investor): array
    {
        $investor->loadMissing([
            'investorCategory.documentRequirements.documentType',
            'documents.documentType',
            'directors',
            'kycProfile',
            'contact',
            'addresses',
        ]);

        $category = $investor->investorCategory;

        $requirements = $category?->documentRequirements
            ?->where('is_active', true)
            ?->where('is_visible_on_onboarding', true)
            ->values() ?? collect();

        $requiredRequirements = $requirements
            ->where('is_required', true)
            ->values();

        $documents = $investor->documents;

        $missingDocuments = [];
        $rejectedDocuments = [];
        $uploadedRequiredCount = 0;
        $verifiedRequiredCount = 0;

        foreach ($requiredRequirements as $requirement) {
            $docTypeId = $requirement->document_type_id;
            $minCount = $requirement->minimum_required_count ?: 1;

            $matchingDocs = $documents
                ->where('document_type_id', $docTypeId)
                ->where('is_current_version', true);

            $uploadedCount = $matchingDocs->count();
            $verifiedCount = $matchingDocs->where('verification_status', 'verified')->count();
            $rejectedCount = $matchingDocs->where('verification_status', 'rejected')->count();

            if ($uploadedCount >= $minCount) {
                $uploadedRequiredCount++;
            } else {
                $missingDocuments[] = [
                    'document_type_id' => $docTypeId,
                    'document_type_code' => $requirement->documentType?->code,
                    'document_type_name' => $requirement->documentType?->name,
                    'minimum_required_count' => $minCount,
                    'uploaded_count' => $uploadedCount,
                ];
            }

            if ($verifiedCount >= $minCount) {
                $verifiedRequiredCount++;
            }

            if ($rejectedCount > 0) {
                $rejectedDocuments[] = [
                    'document_type_id' => $docTypeId,
                    'document_type_code' => $requirement->documentType?->code,
                    'document_type_name' => $requirement->documentType?->name,
                    'rejected_count' => $rejectedCount,
                ];
            }
        }

        $identityVerificationRequired = $this->isInvestorIdentityVerificationRequired($investor);
        $identityVerificationPassed = $this->hasInvestorIdentityVerificationPassed($investor);

        $signingDirectorsRequired = $this->isSigningDirectorVerificationRequired($investor);
        $signingDirectorsVerified = $this->areSigningDirectorsVerified($investor);

        $allRequiredDocumentsUploaded = count($missingDocuments) === 0;
        $allRequiredDocumentsVerified = $verifiedRequiredCount === $requiredRequirements->count();
        $hasRejectedRequiredDocuments = count($rejectedDocuments) > 0;

        $profileCompleted = $this->isProfileCompleted($investor);

        $documentsCompleted = $allRequiredDocumentsVerified && ! $hasRejectedRequiredDocuments;

        $identityPhasePassed = (! $identityVerificationRequired || $identityVerificationPassed)
            && (! $signingDirectorsRequired || $signingDirectorsVerified);

        $kycTier = $this->determineTier(
            identityPhasePassed: $identityPhasePassed,
            profileCompleted: $profileCompleted,
            documentsCompleted: $documentsCompleted
        );

        $canViewProducts = in_array($kycTier, ['tier_1', 'tier_2', 'tier_3'], true);
        $canPurchase = in_array($kycTier, ['tier_2', 'tier_3'], true);
        $canRedeem = $kycTier === 'tier_3';

        return [
            'investor_id' => $investor->id,
            'investor_number' => $investor->investor_number,
            'investor_type' => $investor->investor_type,
            'category' => [
                'id' => $category?->id,
                'code' => $category?->code,
                'name' => $category?->name,
            ],

            // document metrics
            'required_documents_total' => $requiredRequirements->count(),
            'required_documents_uploaded' => $uploadedRequiredCount,
            'required_documents_verified' => $verifiedRequiredCount,
            'all_required_documents_uploaded' => $allRequiredDocumentsUploaded,
            'all_required_documents_verified' => $allRequiredDocumentsVerified,
            'missing_documents' => array_values($missingDocuments),
            'rejected_documents' => array_values($rejectedDocuments),

            // identity checks
            'identity_verification_required' => $identityVerificationRequired,
            'identity_verification_passed' => $identityVerificationPassed,
            'signing_directors_required' => $signingDirectorsRequired,
            'signing_directors_verified' => $signingDirectorsVerified,

            // tier logic
            'identity_verified' => $identityPhasePassed,
            'profile_completed' => $profileCompleted,
            'documents_completed' => $documentsCompleted,
            'kyc_tier' => $kycTier,
            'can_view_products' => $canViewProducts,
            'can_purchase' => $canPurchase,
            'can_redeem' => $canRedeem,
            'is_fully_verified' => $kycTier === 'tier_3',

            // backwards compatibility
            'is_kyc_complete' => $kycTier === 'tier_3',
        ];
    }

    public function syncStatuses(Investor $investor): array
    {
        $summary = $this->evaluate($investor);

        DB::transaction(function () use ($investor, $summary) {
            $onboardingStatus = 'draft';
            $kycStatus = 'pending';
            $investorStatus = 'inactive';

            if ($summary['identity_verified']) {
                $onboardingStatus = 'identity_verified';
                $kycStatus = 'pending';
            }

            if ($summary['profile_completed']) {
                $onboardingStatus = 'profile_completed';
            }

            if (! empty($summary['missing_documents'])) {
                if ($summary['profile_completed']) {
                    $onboardingStatus = 'documents_pending';
                }
            }

            if (! empty($summary['rejected_documents'])) {
                $onboardingStatus = 'documents_pending';
                $kycStatus = 'under_review';
                $investorStatus = 'inactive';
            }

            if ($summary['kyc_tier'] === 'tier_2') {
                $kycStatus = 'under_review';
                $investorStatus = 'inactive';
            }

            if ($summary['kyc_tier'] === 'tier_3') {
                $kycStatus = 'approved';

                // full KYC done; final platform activation may still depend on internal approval rules
                if ($investor->approvalRequests()
                    ->where('approval_type', 'investor_onboarding')
                    ->where('status', 'approved')
                    ->exists()) {
                    $onboardingStatus = 'fully_verified';
                    $investorStatus = 'active';
                } else {
                    $onboardingStatus = 'kyc_under_review';
                    $investorStatus = 'inactive';
                }
            }

            $investor->update([
                'onboarding_status' => $onboardingStatus,
                'kyc_status' => $kycStatus,
                'investor_status' => $investorStatus,
            ]);

            if ($investor->kycProfile) {
                $investor->kycProfile->update([
                    'kyc_tier' => $summary['kyc_tier'],
                    'document_status' => $summary['documents_completed'] ? 'complete' : 'incomplete',
                    'identity_verification_status' => $summary['identity_verified'] ? 'verified' : 'pending',
                    'identity_verified_at' => $summary['identity_verified']
                        ? ($investor->kycProfile->identity_verified_at ?? now())
                        : null,
                    'profile_completed_at' => $summary['profile_completed']
                        ? ($investor->kycProfile->profile_completed_at ?? now())
                        : null,
                    'documents_completed_at' => $summary['documents_completed']
                        ? ($investor->kycProfile->documents_completed_at ?? now())
                        : null,
                ]);
            }
        });

        return $this->evaluate($investor->fresh([
            'investorCategory.documentRequirements.documentType',
            'documents.documentType',
            'directors',
            'kycProfile',
            'contact',
            'addresses',
        ]));
    }

    protected function determineTier(
        bool $identityPhasePassed,
        bool $profileCompleted,
        bool $documentsCompleted
    ): string {
        if (! $identityPhasePassed) {
            return 'tier_0';
        }

        if ($identityPhasePassed && ! $profileCompleted) {
            return 'tier_1';
        }

        if ($identityPhasePassed && $profileCompleted && ! $documentsCompleted) {
            return 'tier_2';
        }

        return 'tier_3';
    }

    protected function isProfileCompleted(Investor $investor): bool
    {
        // phase 2 profile completion for individual investors
        // you can tighten this later, but this is a good first production rule
        $hasName = filled($investor->full_name);
        $hasNationality = filled($investor->nationality);

        $hasAddress = $investor->addresses->isNotEmpty();

        if ($investor->investor_type === 'individual') {
            return $hasName && $hasNationality && $hasAddress;
        }

        if ($investor->investor_type === 'corporate') {
            return filled($investor->company_name) && $hasNationality && $hasAddress;
        }

        return $hasName && $hasAddress;
    }

    protected function isInvestorIdentityVerificationRequired(Investor $investor): bool
    {
        return in_array($investor->investor_type, ['individual'], true);
    }

    protected function hasInvestorIdentityVerificationPassed(Investor $investor): bool
    {
        if (! $this->isInvestorIdentityVerificationRequired($investor)) {
            return false;
        }

        return IdentityVerification::query()
            ->where('entity_type', 'investor')
            ->where('entity_id', $investor->id)
            ->where('status', 'verified')
            ->exists();
    }

    protected function isSigningDirectorVerificationRequired(Investor $investor): bool
    {
        return $investor->investor_type === 'corporate';
    }

    protected function areSigningDirectorsVerified(Investor $investor): bool
    {
        if (! $this->isSigningDirectorVerificationRequired($investor)) {
            return false;
        }

        $signingDirectors = $investor->directors
            ->where('has_signing_authority', true)
            ->values();

        if ($signingDirectors->isEmpty()) {
            return false;
        }

        return $signingDirectors->every(function ($director) {
            return $director->identity_verification_status === 'verified';
        });
    }
}