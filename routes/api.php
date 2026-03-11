<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvestorController;

Route::middleware(['auth:sanctum'])->prefix('investors')->group(function () {
    Route::get('/', [InvestorController::class, 'index']);
    Route::post('/', [InvestorController::class, 'store']);
    Route::get('/{investor}', [InvestorController::class, 'show']);
});