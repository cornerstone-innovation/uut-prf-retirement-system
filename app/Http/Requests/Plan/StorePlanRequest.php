<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // controlled in controller
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:20|unique:plans,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:investor_categories,id',
            'status' => 'required|in:draft,approved,active,inactive',
            'is_default' => 'boolean',
            'investment_objective' => 'nullable|string',
            'target_audience' => 'nullable|string',
        ];
    }
}