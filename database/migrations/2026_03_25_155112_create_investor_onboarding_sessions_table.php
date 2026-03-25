<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_onboarding_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('investor_type', 50);
            $table->string('phone_number', 30);
            $table->string('nida_number', 100)->nullable();

            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('nida_verified_at')->nullable();

            $table->string('current_step', 50)->default('started');
            $table->string('status', 50)->default('pending');

            $table->json('prefill_data')->nullable();
            $table->json('payload_snapshot')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('phone_number');
            $table->index('nida_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_onboarding_sessions');
    }
};