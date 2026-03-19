<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Services\Kyc\KycCompletenessService;

class KycOperationsController extends Controller
{
    public function pendingQueue(
        Request $request,
        KycCompletenessService $kycCompletenessService
    ): JsonResponse {
        abort_unless(
            $request->user()?->can('review kyc') ||
            $request->user()?->can('view approvals') ||
            $request->user()?->can('view investors'),
            403
        );

        $query = Investor::query()
            ->with([
                'contact',
                'investorCategory.documentRequirements.documentType',
                'documents.documentType',
                'directors',
                'kycProfile',
                'kycReviews' => fn ($q) => $q->latest('id'),
            ]);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('investor_number', 'ilike', "%{$search}%")
                    ->orWhere('full_name', 'ilike', "%{$search}%")
                    ->orWhere('company_name', 'ilike', "%{$search}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($search) {
                        $contactQuery->where('email', 'ilike', "%{$search}%")
                            ->orWhere('phone_primary', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($request->filled('investor_type')) {
            $query->where('investor_type', $request->string('investor_type')->toString());
        }

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->string('kyc_status')->toString());
        }

        if ($request->filled('onboarding_status')) {
            $query->where('onboarding_status', $request->string('onboarding_status')->toString());
        }

        if ($request->filled('investor_category_id')) {
            $query->where('investor_category_id', $request->integer('investor_category_id'));
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $paginator = $query
            ->where(function ($q) {
                $q->whereIn('kyc_status', ['pending', 'under_review'])
                    ->orWhereIn('onboarding_status', ['draft', 'pending_documents', 'kyc_under_review']);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $rows = collect($paginator->items())->map(function (Investor $investor) use ($kycCompletenessService) {
            $summary = $kycCompletenessService->evaluate($investor);
            $latestReview = $investor->kycReviews->first();

            return [
                'investor_id' => $investor->id,
                'investor_number' => $investor->investor_number,
                'full_name' => $investor->full_name,
                'company_name' => $investor->company_name,
                'investor_type' => $investor->investor_type,
                'category' => [
                    'id' => $investor->investorCategory?->id,
                    'code' => $investor->investorCategory?->code,
                    'name' => $investor->investorCategory?->name,
                ],
                'onboarding_status' => $investor->onboarding_status,
                'kyc_status' => $investor->kyc_status,
                'investor_status' => $investor->investor_status,
                'required_documents_total' => $summary['required_documents_total'],
                'required_documents_uploaded' => $summary['required_documents_uploaded'],
                'required_documents_verified' => $summary['required_documents_verified'],
                'missing_documents_count' => count($summary['missing_documents']),
                'rejected_documents_count' => count($summary['rejected_documents']),
                'identity_verification_required' => $summary['identity_verification_required'],
                'identity_verification_passed' => $summary['identity_verification_passed'],
                'signing_directors_required' => $summary['signing_directors_required'],
                'signing_directors_verified' => $summary['signing_directors_verified'],
                'is_kyc_complete' => $summary['is_kyc_complete'],
                'latest_kyc_review' => $latestReview ? [
                    'id' => $latestReview->id,
                    'decision' => $latestReview->decision,
                    'review_status' => $latestReview->review_status,
                    'reviewed_at' => optional($latestReview->reviewed_at)?->toDateTimeString(),
                ] : null,
                'created_at' => optional($investor->created_at)?->toDateTimeString(),
            ];
        });

        return response()->json([
            'message' => 'Pending KYC queue retrieved successfully.',
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function missingDocumentsQueue(
        Request $request,
        KycCompletenessService $kycCompletenessService
    ): JsonResponse {
        abort_unless(
            $request->user()?->can('review kyc') ||
            $request->user()?->can('view approvals') ||
            $request->user()?->can('view investors'),
            403
        );

        $query = Investor::query()
            ->with([
                'contact',
                'investorCategory.documentRequirements.documentType',
                'documents.documentType',
                'directors',
                'kycProfile',
            ]);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('investor_number', 'ilike', "%{$search}%")
                    ->orWhere('full_name', 'ilike', "%{$search}%")
                    ->orWhere('company_name', 'ilike', "%{$search}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($search) {
                        $contactQuery->where('email', 'ilike', "%{$search}%")
                            ->orWhere('phone_primary', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($request->filled('investor_type')) {
            $query->where('investor_type', $request->string('investor_type')->toString());
        }

        if ($request->filled('investor_category_id')) {
            $query->where('investor_category_id', $request->integer('investor_category_id'));
        }

        if ($request->filled('onboarding_status')) {
            $query->where('onboarding_status', $request->string('onboarding_status')->toString());
        }

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $paginator = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $rows = collect($paginator->items())
            ->map(function (Investor $investor) use ($kycCompletenessService) {
                $summary = $kycCompletenessService->evaluate($investor);

                if (count($summary['missing_documents']) === 0) {
                    return null;
                }

                return [
                    'investor_id' => $investor->id,
                    'investor_number' => $investor->investor_number,
                    'full_name' => $investor->full_name,
                    'company_name' => $investor->company_name,
                    'investor_type' => $investor->investor_type,
                    'category' => [
                        'id' => $investor->investorCategory?->id,
                        'code' => $investor->investorCategory?->code,
                        'name' => $investor->investorCategory?->name,
                    ],
                    'onboarding_status' => $investor->onboarding_status,
                    'kyc_status' => $investor->kyc_status,
                    'missing_documents_count' => count($summary['missing_documents']),
                    'missing_documents' => $summary['missing_documents'],
                    'created_at' => optional($investor->created_at)?->toDateTimeString(),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'message' => 'Missing documents queue retrieved successfully.',
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total_source_records' => $paginator->total(),
                'returned_records' => $rows->count(),
            ],
        ]);
    }


    public function rejectedDocumentsQueue(
    Request $request,
    KycCompletenessService $kycCompletenessService
): JsonResponse {
    abort_unless(
        $request->user()?->can('review kyc') ||
        $request->user()?->can('view approvals') ||
        $request->user()?->can('view investors'),
        403
    );

    $query = Investor::query()
        ->with([
            'contact',
            'investorCategory.documentRequirements.documentType',
            'documents' => function ($q) {
                $q->with('documentType')
                    ->where('is_current_version', true);
            },
            'directors',
            'kycProfile',
        ]);

    if ($search = $request->string('search')->toString()) {
        $query->where(function ($q) use ($search) {
            $q->where('investor_number', 'ilike', "%{$search}%")
                ->orWhere('full_name', 'ilike', "%{$search}%")
                ->orWhere('company_name', 'ilike', "%{$search}%")
                ->orWhereHas('contact', function ($contactQuery) use ($search) {
                    $contactQuery->where('email', 'ilike', "%{$search}%")
                        ->orWhere('phone_primary', 'ilike', "%{$search}%");
                });
        });
    }

    if ($request->filled('investor_type')) {
        $query->where('investor_type', $request->string('investor_type')->toString());
    }

    if ($request->filled('investor_category_id')) {
        $query->where('investor_category_id', $request->integer('investor_category_id'));
    }

    if ($request->filled('onboarding_status')) {
        $query->where('onboarding_status', $request->string('onboarding_status')->toString());
    }

    $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

    $paginator = $query
        ->latest('id')
        ->paginate($perPage)
        ->withQueryString();

    $rows = collect($paginator->items())
        ->map(function (Investor $investor) use ($kycCompletenessService) {
            $summary = $kycCompletenessService->evaluate($investor);

            if (count($summary['rejected_documents']) === 0) {
                return null;
            }

            $rejectedCurrentDocs = $investor->documents
                ->where('verification_status', 'rejected')
                ->where('is_current_version', true)
                ->values()
                ->map(function ($document) {
                    return [
                        'document_id' => $document->id,
                        'document_type_id' => $document->document_type_id,
                        'document_type_code' => $document->documentType?->code,
                        'document_type_name' => $document->documentType?->name,
                        'original_filename' => $document->original_filename,
                        'verification_notes' => $document->verification_notes,
                        'verified_at' => optional($document->verified_at)?->toDateTimeString(),
                        'version_number' => $document->version_number,
                    ];
                })
                ->values();

            return [
                'investor_id' => $investor->id,
                'investor_number' => $investor->investor_number,
                'full_name' => $investor->full_name,
                'company_name' => $investor->company_name,
                'investor_type' => $investor->investor_type,
                'category' => [
                    'id' => $investor->investorCategory?->id,
                    'code' => $investor->investorCategory?->code,
                    'name' => $investor->investorCategory?->name,
                ],
                'onboarding_status' => $investor->onboarding_status,
                'kyc_status' => $investor->kyc_status,
                'rejected_documents_count' => count($summary['rejected_documents']),
                'rejected_documents' => $summary['rejected_documents'],
                'rejected_current_documents_detail' => $rejectedCurrentDocs,
                'created_at' => optional($investor->created_at)?->toDateTimeString(),
            ];
        })
        ->filter()
        ->values();

    return response()->json([
        'message' => 'Rejected documents queue retrieved successfully.',
        'data' => $rows,
        'meta' => [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total_source_records' => $paginator->total(),
            'returned_records' => $rows->count(),
        ],
    ]);
}

public function escalatedQueue(
    Request $request,
    KycCompletenessService $kycCompletenessService
): JsonResponse {
    abort_unless(
        $request->user()?->can('review kyc') ||
        $request->user()?->can('view approvals') ||
        $request->user()?->can('view investors'),
        403
    );

    $query = Investor::query()
        ->with([
            'contact',
            'investorCategory.documentRequirements.documentType',
            'documents.documentType',
            'directors',
            'kycProfile',
            'kycReviews' => fn ($q) => $q->latest('id'),
        ]);

    if ($search = $request->string('search')->toString()) {
        $query->where(function ($q) use ($search) {
            $q->where('investor_number', 'ilike', "%{$search}%")
                ->orWhere('full_name', 'ilike', "%{$search}%")
                ->orWhere('company_name', 'ilike', "%{$search}%")
                ->orWhereHas('contact', function ($contactQuery) use ($search) {
                    $contactQuery->where('email', 'ilike', "%{$search}%")
                        ->orWhere('phone_primary', 'ilike', "%{$search}%");
                });
        });
    }

    if ($request->filled('investor_type')) {
        $query->where('investor_type', $request->string('investor_type')->toString());
    }

    if ($request->filled('investor_category_id')) {
        $query->where('investor_category_id', $request->integer('investor_category_id'));
    }

    $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

    $paginator = $query
        ->latest('id')
        ->paginate($perPage)
        ->withQueryString();

    $rows = collect($paginator->items())
        ->map(function (Investor $investor) use ($kycCompletenessService) {
            $summary = $kycCompletenessService->evaluate($investor);
            $latestReview = $investor->kycReviews->first();

            $isEscalated = $latestReview &&
                (
                    $latestReview->decision === 'escalated' ||
                    $latestReview->review_status === 'escalated'
                );

            if (! $isEscalated) {
                return null;
            }

            return [
                'investor_id' => $investor->id,
                'investor_number' => $investor->investor_number,
                'full_name' => $investor->full_name,
                'company_name' => $investor->company_name,
                'investor_type' => $investor->investor_type,
                'category' => [
                    'id' => $investor->investorCategory?->id,
                    'code' => $investor->investorCategory?->code,
                    'name' => $investor->investorCategory?->name,
                ],
                'onboarding_status' => $investor->onboarding_status,
                'kyc_status' => $investor->kyc_status,
                'investor_status' => $investor->investor_status,
                'required_documents_total' => $summary['required_documents_total'],
                'required_documents_uploaded' => $summary['required_documents_uploaded'],
                'required_documents_verified' => $summary['required_documents_verified'],
                'missing_documents_count' => count($summary['missing_documents']),
                'rejected_documents_count' => count($summary['rejected_documents']),
                'identity_verification_required' => $summary['identity_verification_required'],
                'identity_verification_passed' => $summary['identity_verification_passed'],
                'signing_directors_required' => $summary['signing_directors_required'],
                'signing_directors_verified' => $summary['signing_directors_verified'],
                'is_kyc_complete' => $summary['is_kyc_complete'],
                'latest_kyc_review' => [
                    'id' => $latestReview->id,
                    'decision' => $latestReview->decision,
                    'review_status' => $latestReview->review_status,
                    'review_notes' => $latestReview->review_notes,
                    'escalation_reason' => $latestReview->escalation_reason,
                    'override_reason' => $latestReview->override_reason,
                    'reviewed_by' => $latestReview->reviewed_by,
                    'reviewed_at' => optional($latestReview->reviewed_at)?->toDateTimeString(),
                ],
                'created_at' => optional($investor->created_at)?->toDateTimeString(),
            ];
        })
        ->filter()
        ->values();

    return response()->json([
        'message' => 'Escalated KYC queue retrieved successfully.',
        'data' => $rows,
        'meta' => [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total_source_records' => $paginator->total(),
            'returned_records' => $rows->count(),
        ],
    ]);
}
}