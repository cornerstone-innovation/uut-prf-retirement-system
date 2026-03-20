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
            'code' => 'sometimes|string|max:20|unique:plans,code,' . $planId,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:investor_categories,id',
            'status' => 'sometimes|in:draft,approved,active,inactive',
            'is_default' => 'boolean',
            'investment_objective' => 'nullable|string',
            'target_audience' => 'nullable|string',
        ];
    }
}