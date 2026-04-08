<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitializePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['mobile_money'])],
            'phone_number' => [
                'required',
                'string',
                'regex:/^255\d{9}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Only mobile money is supported for this payment flow.',
            'phone_number.required' => 'Phone number is required for USSD push payment.',
            'phone_number.regex' => 'Phone number must be in the format 2557XXXXXXXX.',
        ];
    }
}