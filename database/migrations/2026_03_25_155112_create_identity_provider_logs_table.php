<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('identity_provider_logs')) {
            Schema::create('identity_provider_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();

                $table->string('provider', 50);
                $table->string('request_type', 100);
                $table->string('reference')->nullable();

                $table->foreignId('investor_id')->nullable()->constrained('investors')->nullOnDelete();
                $table->foreignId('onboarding_session_id')->nullable()->constrained('investor_onboarding_sessions')->nullOnDelete();

                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();

                $table->string('status', 50)->default('pending');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_provider_logs');
    }
};