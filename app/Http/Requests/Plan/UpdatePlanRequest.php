<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');
        $planId = is_object($plan) ? $plan->id : $plan;

        return [
            'fund_id' => 'sometimes|exists:funds,id',
            'plan_category_id' => 'nullable|exists:plan_categories,id',
            'code' => 'sometimes|string|max:20|unique:plans,code,' . $planId,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,pending_approval,approved,active,inactive',
            'is_default' => 'sometimes|boolean',
            'investment_objective' => 'nullable|string',
            'target_audience' => 'nullable|string',
        ];
    }
}