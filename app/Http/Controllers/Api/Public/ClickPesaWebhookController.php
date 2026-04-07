<?php

namespace App\Http\Controllers\Api\Public;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Services\Payment\ClickPesaWebhookService;
use Illuminate\Validation\ValidationException;

class ClickPesaWebhookController extends Controller
{
    public function handle(
        Request $request,
        ClickPesaWebhookService $webhookService
    ): JsonResponse {
        try {
            $webhookService->handle($request->all());

            return response()->json([
                'message' => 'Webhook received successfully.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Webhook validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Webhook processing failed.',
            ], 500);
        }
    }
}