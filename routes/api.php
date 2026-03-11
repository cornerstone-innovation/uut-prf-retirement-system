<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvestorController;

Route::prefix('investors')->group(function () {
    Route::post('/', [InvestorController::class, 'store']);
});