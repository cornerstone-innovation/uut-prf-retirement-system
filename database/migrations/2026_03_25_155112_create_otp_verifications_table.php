<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('phone_number', 30);
            $table->string('purpose', 50)->default('investor_onboarding');
            $table->string('code', 10);

            $table->string('provider', 50)->default('beem');
            $table->string('provider_reference')->nullable();

            $table->string('status', 50)->default('pending');
            $table->unsignedInteger('attempts')->default(0);

            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['phone_number', 'purpose']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};