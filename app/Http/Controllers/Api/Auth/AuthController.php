<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;

use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with(['roles'])
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'This user account is inactive.',
            ], 403);
        }

        if ($user->hasRole('investor')) {
            return response()->json([
                'message' => 'This login portal is for internal users only.',
            ], 403);
        }

        $token = $user->createToken('ops-web')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->getRoleNames()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
        ]);
    }

    public function investorLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->with(['roles', 'investor'])
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'This user account is inactive.',
            ], 403);
        }

        if (! $user->hasRole('investor')) {
            return response()->json([
                'message' => 'This login portal is for investors only.',
            ], 403);
        }

        if (! $user->investor) {
            return response()->json([
                'message' => 'No investor profile is linked to this account.',
            ], 403);
        }

        $token = $user->createToken('investor-web')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->getRoleNames()->values(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
                'investor' => [
                    'id' => $user->investor->id,
                    'uuid' => $user->investor->uuid,
                    'investor_number' => $user->investor->investor_number,
                    'full_name' => $user->investor->full_name,
                ],
            ],
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user()?->loadMissing(['roles', 'investor']);

        return response()->json([
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'id' => $user?->id,
                'uuid' => $user?->uuid,
                'name' => $user?->name,
                'email' => $user?->email,
                'phone' => $user?->phone,
                'roles' => $user?->getRoleNames()->values() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name')->values() ?? [],
                'investor' => $user?->investor ? [
                    'id' => $user->investor->id,
                    'uuid' => $user->investor->uuid,
                    'investor_number' => $user->investor->investor_number,
                    'full_name' => $user->investor->full_name,
                ] : null,
            ],
        ]);
    }
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json([
                'message' => __($status),
            ])
            : response()->json([
                'message' => __($status),
            ], 422);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json([
                'message' => __($status),
            ])
            : response()->json([
                'message' => __($status),
            ], 422);
    }

public function sendVerificationEmail(Request $request): JsonResponse
{
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'Email is already verified.',
        ]);
    }

    $request->user()->sendEmailVerificationNotification();

    return response()->json([
        'message' => 'Verification link sent successfully.',
    ]);
}

public function verifyEmail(EmailVerificationRequest $request): JsonResponse
{
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'Email already verified.',
        ]);
    }

    if ($request->user()->markEmailAsVerified()) {
        event(new Verified($request->user()));
    }

    return response()->json([
        'message' => 'Email verified successfully.',
    ]);
}
}