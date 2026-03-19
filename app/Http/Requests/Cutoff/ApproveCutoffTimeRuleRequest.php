<?php

namespace App\Http\Requests\Cutoff;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCutoffTimeRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}