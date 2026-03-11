<?php

namespace App\Providers;

use App\Models\Investor;
use App\Policies\InvestorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Investor::class, InvestorPolicy::class);
    }
}