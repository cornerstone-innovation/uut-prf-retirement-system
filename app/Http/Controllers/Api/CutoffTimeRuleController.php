<?php

namespace App\Http\Controllers\Api;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\CutoffTimeRule;
use App\Http\Resources\CutoffTimeRuleResource;
use App\Http\Requests\Cutoff\StoreCutoffTimeRuleRequest;
use App\Http\Requests\Cutoff\ApproveCutoffTimeRuleRequest;
use App\Http\Requests\Cutoff\ActivateCutoffTimeRuleRequest;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Services\Nav\CutoffTimeRuleService;

class CutoffTimeRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage cutoff rules'),
            403
        );

        $query = CutoffTimeRule::query()->with('plan');

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->integer('plan_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $rules = $query->latest('id')->get();

        return response()->json([
            'message' => 'Cutoff time rules retrieved successfully.',
            'data' => CutoffTimeRuleResource::collection($rules),
        ]);
    }

    public function store(
        StoreCutoffTimeRuleRequest $request,
        CutoffTimeRuleService $service,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('manage cutoff rules'),
            403
        );

        $plan = $request->filled('plan_id')
            ? Plan::findOrFail($request->integer('plan_id'))
            : null;

        $rule = $service->create(
            plan: $plan,
            cutoffTime: $request->input('cutoff_time'),
            timezone: $request->input('timezone'),
            effectiveFrom: $request->input('effective_from'),
            effectiveTo: $request->input('effective_to'),
            notes: $request->input('notes'),
            createdBy: $request->user()?->id,
        );

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'cutoff_rule.created',
            entityType: 'cutoff_time_rule',
            entityId: $rule->id,
            entityReference: $rule->uuid,
            metadata: [
                'plan_id' => $rule->plan_id,
                'cutoff_time' => $rule->cutoff_time,
                'timezone' => $rule->timezone,
                'status' => $rule->status,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Cutoff time rule created successfully.',
            'data' => new CutoffTimeRuleResource($rule->load('plan')),
        ], 201);
    }

    public function approve(
        ApproveCutoffTimeRuleRequest $request,
        CutoffTimeRule $cutoffTimeRule,
        CutoffTimeRuleService $service,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('manage cutoff rules'),
            403
        );

        $rule = $service->approve(
            rule: $cutoffTimeRule,
            actedBy: $request->user()->id,
            notes: $request->input('notes'),
        );

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'cutoff_rule.approved',
            entityType: 'cutoff_time_rule',
            entityId: $rule->id,
            entityReference: $rule->uuid,
            metadata: [
                'status' => $rule->status,
                'approved_by' => $rule->approved_by,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Cutoff time rule approved successfully.',
            'data' => new CutoffTimeRuleResource($rule->load('plan')),
        ]);
    }

    public function activate(
        ActivateCutoffTimeRuleRequest $request,
        CutoffTimeRule $cutoffTimeRule,
        CutoffTimeRuleService $service,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('manage cutoff rules'),
            403
        );

        $rule = $service->activate(
            rule: $cutoffTimeRule,
            notes: $request->input('notes'),
        );

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'cutoff_rule.activated',
            entityType: 'cutoff_time_rule',
            entityId: $rule->id,
            entityReference: $rule->uuid,
            metadata: [
                'status' => $rule->status,
                'is_active' => $rule->is_active,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Cutoff time rule activated successfully.',
            'data' => new CutoffTimeRuleResource($rule->load('plan')),
        ]);
    }
}