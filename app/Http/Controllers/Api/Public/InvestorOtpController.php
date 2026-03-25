<?php

namespace App\Http\Controllers\Api\Public;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\SendPhoneOtpRequest;
use App\Http\Requests\Onboarding\VerifyPhoneOtpRequest;

class InvestorOtpController extends Controller
{
    public function send(
        SendPhoneOtpRequest $request
    ): JsonResponse {
        return response()->json([
            'message' => 'OTP send route is working.',
            'data' => [
                'session_uuid' => $request->input('session_uuid'),
                'phone_number' => $request->input('phone_number'),
            ],
        ]);
    }

    public function verify(
        VerifyPhoneOtpRequest $request
    ): JsonResponse {
        return response()->json([
            'message' => 'OTP verify route is working.',
            'data' => [
                'session_uuid' => $request->input('session_uuid'),
                'phone_number' => $request->input('phone_number'),
                'otp_code' => $request->input('otp_code'),
            ],
        ]);
    }
}