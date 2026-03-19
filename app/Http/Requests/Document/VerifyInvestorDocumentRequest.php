<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class VerifyInvestorDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'verification_notes' => ['nullable', 'string'],
        ];
    }
}