<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();

            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('provider')->default('clickpesa');
            $table->string('status')->default('pending'); // pending, initiated, callback_received, success, failed

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['payment_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};