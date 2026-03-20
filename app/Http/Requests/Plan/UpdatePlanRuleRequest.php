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
        $planId = $this->route('plan')->id;

        return [
            'fund_id' => ['sometimes', 'required', 'exists:funds,id'],
            'plan_category_id' => ['sometimes', 'nullable', 'exists:plan_categories,id'],
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:plans,code,' . $planId],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:draft,pending_approval,approved,active,inactive'],
            'is_default' => ['sometimes', 'boolean'],
            'investment_objective' => ['sometimes', 'nullable', 'string', 'max:255'],
            'target_audience' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}