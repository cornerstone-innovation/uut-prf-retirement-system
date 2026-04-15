<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use App\Models\NavRecord;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\NavRecordResource;
use App\Http\Requests\Nav\StoreNavRecordRequest;
use App\Http\Requests\Nav\ApproveNavRecordRequest;
use App\Http\Requests\Nav\PublishNavRecordRequest;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Services\Nav\NavRecordService;
use App\Application\Services\Purchase\AutoAllocationService;

class NavRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('view nav records'),
            403
        );

        $query = NavRecord::query()->with([
            'plan',
            'approverOne',
            'approverTwo',
            'publisher',
        ]);

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->integer('plan_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('valuation_date')) {
            $query->whereDate('valuation_date', $request->string('valuation_date')->toString());
        }

        $records = $query->latest('valuation_date')->latest('id')->get();

        return response()->json([
            'message' => 'NAV records retrieved successfully.',
            'data' => NavRecordResource::collection($records),
        ]);
    }

    public function store(
        StoreNavRecordRequest $request,
        NavRecordService $service,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('create nav records'),
            403
        );

        $plan = Plan::findOrFail($request->integer('plan_id'));

        $record = $service->create(
            plan: $plan,
            valuationDate: $request->input('valuation_date'),
            navPerUnit: (float) $request->input('nav_per_unit'),
            notes: $request->input('notes'),
            createdBy: $request->user()?->id,
        );

        $record->load([
            'plan',
            'approverOne',
            'approverTwo',
            'publisher',
        ]);

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'nav_record.created',
            entityType: 'nav_record',
            entityId: $record->id,
            entityReference: $record->uuid,
            metadata: [
                'plan_id' => $record->plan_id,
                'valuation_date' => $record->valuation_date?->toDateString(),
                'nav_per_unit' => $record->nav_per_unit,
                'status' => $record->status,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'NAV record created successfully.',
            'data' => new NavRecordResource($record),
        ], 201);
    }

    public function show(NavRecord $navRecord): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('view nav records'),
            403
        );

        $navRecord->load([
            'plan',
            'approverOne',
            'approverTwo',
            'publisher',
        ]);

        return response()->json([
            'message' => 'NAV record retrieved successfully.',
            'data' => new NavRecordResource($navRecord),
        ]);
    }

    public function approve(
        ApproveNavRecordRequest $request,
        NavRecord $navRecord,
        NavRecordService $service,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('approve nav records'),
            403
        );

        $record = $service->approve(
            navRecord: $navRecord,
            actedBy: $request->user()->id,
            notes: $request->input('notes'),
        );

        $record->load([
            'plan',
            'approverOne',
            'approverTwo',
            'publisher',
        ]);

        $auditLogger->log(
            userId: $request->user()->id,
            action: 'nav_record.approved',
            entityType: 'nav_record',
            entityId: $record->id,
            entityReference: $record->uuid,
            metadata: [
                'status' => $record->status,
                'approved_by_1' => $record->approved_by_1,
                'approved_by_1_name' => $record->approverOne?->name,
                'approved_by_2' => $record->approved_by_2,
                'approved_by_2_name' => $record->approverTwo?->name,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'NAV record approved successfully.',
            'data' => new NavRecordResource($record),
        ]);
    }

    public function publish(
        PublishNavRecordRequest $request,
        NavRecord $navRecord,
        NavRecordService $service,
        AutoAllocationService $autoAllocationService,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('publish nav records'),
            403
        );

        $record = $service->publish(
            navRecord: $navRecord,
            actedBy: $request->user()->id,
            notes: $request->input('notes'),
        );

        $allocationSummary = $autoAllocationService->allocateForPublishedNav(
            navRecord: $record,
            processedBy: $request->user()?->id,
        );

        $record->load([
            'plan',
            'approverOne',
            'approverTwo',
            'publisher',
        ]);

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'nav_record.published',
            entityType: 'nav_record',
            entityId: $record->id,
            entityReference: $record->uuid,
            metadata: [
                'status' => $record->status,
                'published_by' => $record->published_by,
                'published_by_name' => $record->publisher?->name,
                'published_at' => optional($record->published_at)?->toDateTimeString(),
                'auto_allocation' => [
                    'matched_requests' => $allocationSummary['matched_requests'],
                    'allocated_count' => $allocationSummary['allocated_count'],
                    'failed_count' => $allocationSummary['failed_count'],
                ],
            ],
            request: $request
        );

        return response()->json([
            'message' => 'NAV record published successfully.',
            'data' => [
                'nav_record' => new NavRecordResource($record),
                'auto_allocation' => $allocationSummary,
            ],
        ]);
    }
}