<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Requests\Payment\InitializePaymentRequest;
use App\Http\Requests\Payment\MockPaymentCallbackRequest;
use App\Application\Services\Audit\AuditLogger;
use App\Application\Services\Payment\PaymentService;
use App\Models\PaymentCallback;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function initialize(
        InitializePaymentRequest $request,
        PurchaseRequest $purchaseRequest,
        PaymentService $paymentService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $investor = $request->user()?->investor;

        if (! $investor || (int) $investor->id !== (int) $purchaseRequest->investor_id) {
            return response()->json([
                'message' => 'You are not allowed to initialize payment for this purchase request.',
            ], 403);
        }

        if ($purchaseRequest->status === 'awaiting_next_nav_confirmation') {
            return response()->json([
                'message' => 'This purchase request was submitted after cutoff. Please wait for the next active NAV and reconfirm before payment.',
                'errors' => [
                    'purchase_request' => [
                        'This purchase request was submitted after cutoff. Please wait for the next active NAV and reconfirm before payment.',
                    ],
                ],
            ], 422);
        }

        try {
            $result = $paymentService->initialize(
                purchaseRequest: $purchaseRequest,
                paymentMethod: $request->input('payment_method'),
                phoneNumber: $request->input('phone_number'),
                createdBy: $request->user()->id,
            );

            $auditLogger->log(
                userId: $request->user()->id,
                action: 'payment.initialized',
                entityType: 'payment',
                entityId: $result['payment']->id,
                entityReference: $result['payment']->reference,
                metadata: [
                    'purchase_request_id' => $purchaseRequest->id,
                    'payment_method' => $request->input('payment_method'),
                    'phone_number' => $request->input('phone_number'),
                    'status' => $result['payment']->status,
                    'provider_status' => $result['provider_status'] ?? null,
                    'provider_transaction_id' => $result['provider_transaction_id'] ?? null,
                ],
                request: $request
            );

            return response()->json([
                'message' => 'Payment initialized successfully.',
                'data' => [
                    'payment' => new PaymentResource($result['payment']),
                    'preview_response' => $result['preview_response'] ?? null,
                    'initiate_response' => $result['initiate_response'] ?? null,
                    'provider_status' => $result['provider_status'] ?? null,
                    'provider_transaction_id' => $result['provider_transaction_id'] ?? null,
                    'payment_instructions' => [
                        'message' => 'USSD push request sent successfully. Please complete the payment on your phone.',
                        'phone_number' => $request->input('phone_number'),
                    ],
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Payment initialization failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Payment initialization failed.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace_hint' => class_basename($e),
            ], 500);
        }
    }

    public function syncStatus(
        Payment $payment,
        PaymentService $paymentService,
        AuditLogger $auditLogger
    ): JsonResponse {
        try {
            $user = request()->user();
            $investor = $user?->investor;

            if ($investor && (int) $investor->id !== (int) $payment->investor_id) {
                return response()->json([
                    'message' => 'You are not allowed to sync this payment.',
                ], 403);
            }

            $payment = $paymentService->syncPaymentStatus($payment);

            $auditLogger->log(
                userId: $user?->id,
                action: 'payment.status_synced',
                entityType: 'payment',
                entityId: $payment->id,
                entityReference: $payment->reference,
                metadata: [
                    'status' => $payment->status,
                    'purchase_request_status' => $payment->purchaseRequest?->status,
                    'provider_reference' => $payment->provider_reference,
                ],
                request: request()
            );

            return response()->json([
                'message' => 'Payment status synced successfully.',
                'data' => [
                    'payment' => new PaymentResource($payment->load('purchaseRequest')),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Payment status sync failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Payment status sync failed.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace_hint' => class_basename($e),
            ], 500);
        }
    }

    public function mockCallback(
        MockPaymentCallbackRequest $request,
        PaymentService $paymentService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $payment = Payment::with(['purchaseRequest', 'attempts'])->findOrFail($request->integer('payment_id'));

        $callback = PaymentCallback::create([
            'uuid' => (string) Str::uuid(),
            'payment_id' => $payment->id,
            'provider' => 'clickpesa',
            'provider_reference' => $payment->provider_reference,
            'payload' => $request->validated(),
            'processed' => false,
        ]);

        $payment = $request->input('status') === 'paid'
            ? $paymentService->markPaid($payment, $request->validated())
            : $paymentService->markFailed($payment, $request->validated());

        $callback->update([
            'processed' => true,
            'processed_at' => now(),
        ]);

        $auditLogger->log(
            userId: auth()->id(),
            action: 'payment.mock_callback_processed',
            entityType: 'payment',
            entityId: $payment->id,
            entityReference: $payment->reference,
            metadata: [
                'status' => $payment->status,
                'purchase_request_status' => $payment->purchaseRequest?->status,
            ],
            request: request()
        );

        return response()->json([
            'message' => 'Mock payment callback processed successfully.',
            'data' => [
                'payment' => new PaymentResource($payment->load('purchaseRequest')),
            ],
        ]);
    }
}