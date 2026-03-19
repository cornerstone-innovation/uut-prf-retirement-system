<?php

namespace App\Http\Requests\Cutoff;

use Illuminate\Foundation\Http\FormRequest;

class StoreCutoffTimeRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['nullable', 'exists:plans,id'],
            'cutoff_time' => ['required', 'date_format:H:i:s'],
            'timezone' => ['required', 'string'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string'],
        ];
    }
}