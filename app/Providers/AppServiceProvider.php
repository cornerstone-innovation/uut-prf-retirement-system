<?php

namespace App\Providers;

use App\Models\Investor;
use App\Policies\InvestorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Investor::class, InvestorPolicy::class);

        ResetPassword::createUrlUsing(function (object $user, string $token) {
            $frontendUrl = config('app.frontend_url');

            return $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });

        VerifyEmail::createUrlUsing(function (object $user) {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );
        });
    }
}