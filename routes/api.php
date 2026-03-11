<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvestorController;

Route::middleware(['auth:sanctum'])->prefix('investors')->group(function () {
    Route::post('/', [InvestorController::class, 'store']);
});