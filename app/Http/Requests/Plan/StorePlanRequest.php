<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fund_id' => ['required', 'exists:funds,id'],
            'plan_category_id' => ['nullable', 'exists:plan_categories,id'],
            'code' => ['required', 'string', 'max:50', 'unique:plans,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,pending_approval,approved,active,inactive'],
            'is_default' => ['nullable', 'boolean'],
            'investment_objective' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}