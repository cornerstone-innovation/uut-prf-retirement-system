<?php

namespace App\Providers;

use App\Models\Investor;
use App\Policies\InvestorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use App\Policies\InvestorDocumentPolicy;
use App\Models\CompanyDirector;
use App\Models\InvestorDocument;
use App\Models\IdentityVerification;
use App\Observers\CompanyDirectorObserver;
use App\Observers\InvestorDocumentObserver;
use App\Observers\IdentityVerificationObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(InvestorDocument::class, InvestorDocumentPolicy::class);
        Gate::policy(Investor::class, InvestorPolicy::class);
        InvestorDocument::observe(InvestorDocumentObserver::class);
        CompanyDirector::observe(CompanyDirectorObserver::class);
        IdentityVerification::observe(IdentityVerificationObserver::class);

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