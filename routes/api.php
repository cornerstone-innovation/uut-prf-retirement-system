<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvestorController;
use App\Http\Controllers\Api\Auth\AuthController;

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware(['auth:sanctum'])->post('/logout', [AuthController::class, 'logout']);
Route::middleware(['auth:sanctum'])->post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail']);
Route::middleware(['auth:sanctum', 'signed'])->get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('verification.verify');

Route::middleware(['auth:sanctum'])->prefix('investors')->group(function () {
    Route::get('/', [InvestorController::class, 'index']);
    Route::post('/', [InvestorController::class, 'store']);
    Route::get('/{investor}', [InvestorController::class, 'show']);
    Route::post('/{investor}/approve', [InvestorController::class, 'approve']);
    Route::post('/{investor}/reject', [InvestorController::class, 'reject']);
});

