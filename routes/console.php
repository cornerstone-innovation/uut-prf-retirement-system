<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Jobs (EAT - Africa/Dar_es_Salaam)
|--------------------------------------------------------------------------
*/

Schedule::command('market-data:sync-dse-price-snapshots')
    ->weekdays()
    ->timezone('Africa/Dar_es_Salaam')
    ->dailyAt('16:10');

Schedule::command('nav:calculate-daily')
    ->weekdays()
    ->timezone('Africa/Dar_es_Salaam')
    ->dailyAt('16:20');