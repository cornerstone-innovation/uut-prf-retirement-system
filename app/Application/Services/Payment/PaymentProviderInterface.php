<?php

namespace App\Application\Services\Payment;

use App\Models\Payment;

interface PaymentProviderInterface
{
    public function initialize(Payment $payment, array $payload = []): array;
}