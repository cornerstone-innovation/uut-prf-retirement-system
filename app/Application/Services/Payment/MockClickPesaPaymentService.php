<?php

namespace App\Application\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Str;

class MockClickPesaPaymentService implements PaymentProviderInterface
{
    public function initialize(Payment $payment, array $payload = []): array
    {
        $providerReference = 'MOCK-CP-' . strtoupper(Str::random(12));

        return [
            'provider' => 'clickpesa',
            'provider_reference' => $providerReference,
            'status' => 'pending',
            'checkout_url' => url("/mock/clickpesa/checkout/{$providerReference}"),
            'payment_instructions' => [
                'message' => 'Mock ClickPesa payment initialized successfully.',
                'channels' => ['mobile_money', 'card'],
            ],
            'raw_response' => [
                'success' => true,
                'provider_reference' => $providerReference,
                'checkout_url' => url("/mock/clickpesa/checkout/{$providerReference}"),
            ],
        ];
    }
}