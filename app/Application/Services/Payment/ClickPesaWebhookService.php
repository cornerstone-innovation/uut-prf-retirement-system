<?php

namespace App\Application\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentCallback;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClickPesaWebhookService
{
    public function __construct(
        private readonly ClickPesaChecksumService $checksumService,
        private readonly PaymentService $paymentService
    ) {
    }

    public function handle(array $payload): array
    {
        $secret = config('services.clickpesa.webhook_secret');

        if (! $secret) {
            throw ValidationException::withMessages([
                'clickpesa' => ['ClickPesa webhook secret is not configured.'],
            ]);
        }

        $receivedChecksum = $payload['checksum'] ?? null;

        if (! $this->checksumService->validate($secret, $payload, $receivedChecksum)) {
            throw ValidationException::withMessages([
                'checksum' => ['Invalid ClickPesa webhook checksum.'],
            ]);
        }

        $providerReference = data_get($payload, 'data.paymentReference');
        $orderReference = data_get($payload, 'data.orderReference');
        $event = $payload['event'] ?? null;
        $status = data_get($payload, 'data.status');

        $payment = Payment::query()
            ->where('provider', 'clickpesa')
            ->where(function ($q) use ($providerReference, $orderReference) {
                $q->where('provider_reference', $providerReference)
                  ->orWhere('reference', $orderReference);
            })
            ->with(['purchaseRequest', 'attempts'])
            ->first();

        if (! $payment) {
            throw ValidationException::withMessages([
                'payment' => ['Matching payment could not be found for webhook payload.'],
            ]);
        }

        return DB::transaction(function () use ($payment, $payload, $providerReference, $event, $status) {
            $callback = PaymentCallback::create([
                'uuid' => (string) Str::uuid(),
                'payment_id' => $payment->id,
                'provider' => 'clickpesa',
                'provider_reference' => $providerReference,
                'payload' => $payload,
                'processed' => false,
            ]);

            if ($event === 'PAYMENT RECEIVED' || $status === 'SUCCESS') {
                $updatedPayment = $this->paymentService->markPaid($payment, $payload);
            } elseif ($event === 'PAYMENT FAILED' || $status === 'FAILED') {
                $updatedPayment = $this->paymentService->markFailed($payment, $payload);
            } else {
                $updatedPayment = $payment->fresh(['purchaseRequest', 'attempts']);
            }

            $callback->update([
                'processed' => true,
                'processed_at' => now(),
            ]);

            return [
                'payment' => $updatedPayment,
                'callback' => $callback->fresh(),
            ];
        });
    }
}