<?php

namespace App\Http\Controllers\Api;

use App\Models\BusinessHoliday;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessHolidayResource;
use App\Http\Requests\Holiday\StoreBusinessHolidayRequest;
use App\Http\Requests\Holiday\UpdateBusinessHolidayRequest;
use App\Application\Services\Audit\AuditLogger;

class BusinessHolidayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage cutoff rules'),
            403
        );

        $query = BusinessHoliday::query();

        if ($request->filled('year')) {
            $year = (int) $request->integer('year');
            $query->whereYear('holiday_date', $year);
        }

        $holidays = $query->orderBy('holiday_date')->get();

        return response()->json([
            'message' => 'Business holidays retrieved successfully.',
            'data' => BusinessHolidayResource::collection($holidays),
        ]);
    }

    public function store(
        StoreBusinessHolidayRequest $request,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('manage cutoff rules'),
            403
        );

        $holiday = BusinessHoliday::create([
            'uuid' => (string) Str::uuid(),
            'holiday_date' => $request->input('holiday_date'),
            'name' => $request->input('name'),
            'country_code' => $request->input('country_code', 'TZ'),
            'status' => 'active',
            'is_active' => true,
            'notes' => $request->input('notes'),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'business_holiday.created',
            entityType: 'business_holiday',
            entityId: $holiday->id,
            entityReference: $holiday->uuid,
            metadata: [
                'holiday_date' => $holiday->holiday_date?->toDateString(),
                'name' => $holiday->name,
            ],
            request: $request
        );

        return response()->json([
            'message' => 'Business holiday created successfully.',
            'data' => new BusinessHolidayResource($holiday),
        ], 201);
    }

    public function update(
        UpdateBusinessHolidayRequest $request,
        BusinessHoliday $businessHoliday,
        AuditLogger $auditLogger
    ): JsonResponse {
        abort_unless(
            auth()->user()?->can('manage cutoff rules'),
            403
        );

        $businessHoliday->update(array_merge(
            $request->validated(),
            ['updated_by' => $request->user()?->id]
        ));

        $auditLogger->log(
            userId: $request->user()?->id,
            action: 'business_holiday.updated',
            entityType: 'business_holiday',
            entityId: $businessHoliday->id,
            entityReference: $businessHoliday->uuid,
            metadata: $request->validated(),
            request: $request
        );

        return response()->json([
            'message' => 'Business holiday updated successfully.',
            'data' => new BusinessHolidayResource($businessHoliday->fresh()),
        ]);
    }
}