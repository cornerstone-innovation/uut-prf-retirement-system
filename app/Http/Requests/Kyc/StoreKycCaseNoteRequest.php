<?php

namespace App\Http\Requests\Kyc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKycCaseNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string'],
            'note_type' => ['nullable', Rule::in(['internal', 'escalation', 'review', 'handoff'])],
            'is_pinned' => ['nullable', 'boolean'],
        ];
    }
}