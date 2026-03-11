<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvestorController;
use App\Http\Controllers\Api\Auth\AuthController;

Route::middleware(['auth:sanctum'])->post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth:sanctum'])->prefix('investors')->group(function () {
    Route::get('/', [InvestorController::class, 'index']);
    Route::post('/', [InvestorController::class, 'store']);
    Route::get('/{investor}', [InvestorController::class, 'show']);
});