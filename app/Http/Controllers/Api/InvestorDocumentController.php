<?php

namespace App\Http\Controllers\Api;

use App\Models\Investor;
use App\Models\DocumentType;
use App\Models\InvestorDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Resources\InvestorDocumentResource;
use App\Http\Requests\Document\UploadInvestorDocumentRequest;
use App\Http\Requests\Document\VerifyInvestorDocumentRequest;
use App\Http\Requests\Document\RejectInvestorDocumentRequest;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Services\Document\InvestorDocumentStorageService;
use App\Application\Services\Document\InvestorDocumentVerificationService;
use App\Application\Services\Kyc\KycCompletenessService;

class InvestorDocumentController extends Controller
{
    public function store(
        UploadInvestorDocumentRequest $request,
        Investor $investor,
        InvestorDocumentStorageService $storageService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $this->authorize('create', InvestorDocument::class);

        $documentType = DocumentType::findOrFail($request->integer('document_type_id'));

        $stored = $storageService->storeEncrypted(
            file: $request->file('file'),
            investorId: $investor->id,
            documentCode: $documentType->code,
            disk: 's3'
        );

        $document = InvestorDocument::create([
            'uuid' => (string) Str::uuid(),
            'investor_id' => $investor->id,
            'investor_kyc_profile_id' => $investor->kycProfile?->id,
            'investor_category_id' => $investor->investor_category_id,
            'document_type_id' => $documentType->id,
            'original_filename' => $stored['original_filename'],
            'stored_filename' => $stored['stored_filename'],
            'storage_disk' => $stored['storage_disk'],
            'storage_path' => $stored['storage_path'],
            'mime_type' => $stored['mime_type'],
            'file_extension' => $stored['file_extension'],
            'file_size_bytes' => $stored['file_size_bytes'],
            'document_number' => $request->input('document_number'),
            'issue_date' => $request->input('issue_date'),
            'expiry_date' => $request->input('expiry_date'),
            'verification_status' => 'pending',
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
            'metadata' => [
                'document_type_code' => $documentType->code,
            ],
        ]);

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'investor_document.uploaded',
            entityType: 'investor_document',
            entityId: $document->id,
            entityReference: $documentType->code,
            metadata: [
                'investor_id' => $investor->id,
                'investor_number' => $investor->investor_number,
                'document_type' => $documentType->code,
                'original_filename' => $document->original_filename,
            ],
            request: $request
        );

        $kycSummary = app(KycCompletenessService::class)->evaluate(
            $investor->fresh([
                'investorCategory.documentRequirements.documentType',
                'documents.documentType',
                'directors',
                'kycProfile',
            ])
        );

        return response()->json([
            'message' => 'Investor document uploaded successfully.',
            'data' => [
                'document' => new InvestorDocumentResource($document->load('documentType')),
                'kyc_summary' => $kycSummary,
            ],
        ], 201);
    }

 

    public function reject(
        RejectInvestorDocumentRequest $request,
        InvestorDocument $investorDocument,
        InvestorDocumentVerificationService $verificationService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $this->authorize('reject', $investorDocument);

        $result = $verificationService->reject(
            document: $investorDocument,
            reviewedBy: $request->user()->id,
            notes: $request->input('verification_notes')
        );

        $auditLogger->log(
            userId: $request->user()->id,
            action: 'investor_document.rejected',
            entityType: 'investor_document',
            entityId: $result['document']->id,
            entityReference: $result['document']->documentType?->code,
            metadata: [
                'investor_id' => $result['document']->investor_id,
                'verification_status' => $result['document']->verification_status,
                'verification_notes' => $result['document']->verification_notes,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Investor document rejected successfully.',
            'data' => [
                'document' => new InvestorDocumentResource($result['document']),
                'kyc_summary' => $result['kyc_summary'],
            ],
        ]);
    }

    public function view(
        InvestorDocument $investorDocument,
        InvestorDocumentStorageService $storageService,
        AuditLogger $auditLogger
    ): StreamedResponse {
        $this->authorize('view', $investorDocument);

        if (! $storageService->exists($investorDocument->storage_disk, $investorDocument->storage_path)) {
            abort(404, 'Document file not found in storage.');
        }

        $contents = $storageService->getDecryptedContents(
            $investorDocument->storage_disk,
            $investorDocument->storage_path
        );

        $auditLogger->log(
            userId: auth()->id(),
            action: 'investor_document.viewed',
            entityType: 'investor_document',
            entityId: $investorDocument->id,
            entityReference: $investorDocument->documentType?->code,
            metadata: [
                'investor_id' => $investorDocument->investor_id,
                'original_filename' => $investorDocument->original_filename,
                'mime_type' => $investorDocument->mime_type,
            ],
            request: request()
        );

        return response()->streamDownload(
            fn () => print($contents),
            $investorDocument->original_filename,
            [
                'Content-Type' => $investorDocument->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . addslashes($investorDocument->original_filename) . '"',
            ]
        );
    }

    public function download(
        InvestorDocument $investorDocument,
        InvestorDocumentStorageService $storageService,
        AuditLogger $auditLogger
    ): StreamedResponse {
        $this->authorize('download', $investorDocument);

        if (! $storageService->exists($investorDocument->storage_disk, $investorDocument->storage_path)) {
            abort(404, 'Document file not found in storage.');
        }

        $contents = $storageService->getDecryptedContents(
            $investorDocument->storage_disk,
            $investorDocument->storage_path
        );

        $auditLogger->log(
            userId: auth()->id(),
            action: 'investor_document.downloaded',
            entityType: 'investor_document',
            entityId: $investorDocument->id,
            entityReference: $investorDocument->documentType?->code,
            metadata: [
                'investor_id' => $investorDocument->investor_id,
                'original_filename' => $investorDocument->original_filename,
                'mime_type' => $investorDocument->mime_type,
            ],
            request: request()
        );

        return response()->streamDownload(
            fn () => print($contents),
            $investorDocument->original_filename,
            [
                'Content-Type' => $investorDocument->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . addslashes($investorDocument->original_filename) . '"',
            ]
        );
    }

    public function history(InvestorDocument $investorDocument): JsonResponse
    {
        $this->authorize('view', $investorDocument);

        $rootId = $investorDocument->parent_document_id
            ? $this->resolveRootDocumentId($investorDocument)
            : $investorDocument->id;

        $documents = InvestorDocument::query()
            ->where(function ($query) use ($rootId) {
                $query->where('id', $rootId)
                    ->orWhere('parent_document_id', $rootId);
            })
            ->with('documentType')
            ->orderBy('version_number')
            ->get();

        return response()->json([
            'message' => 'Investor document history retrieved successfully.',
            'data' => InvestorDocumentResource::collection($documents),
        ]);
    }

    protected function resolveRootDocumentId(InvestorDocument $document): int
    {
        $current = $document;

        while ($current->parentDocument) {
            $current = $current->parentDocument;
        }

        return $current->id;
    }
}