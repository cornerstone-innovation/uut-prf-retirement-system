<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_verifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');

            $table->string('provider')->default('smile_id');
            $table->string('verification_type');

            $table->string('status')->default('pending');
            $table->string('provider_reference')->nullable();

            $table->decimal('score', 5, 2)->nullable();
            $table->text('failure_reason')->nullable();

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('provider');
            $table->index('verification_type');
            $table->index('status');
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_verifications');
    }
};