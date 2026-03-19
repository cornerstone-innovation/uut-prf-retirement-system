<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('investor_id')
                ->constrained('investors')
                ->cascadeOnDelete();

            $table->string('review_status')->default('pending');
            $table->string('decision')->nullable(); // approved | rejected | escalated

            $table->text('review_notes')->nullable();
            $table->text('escalation_reason')->nullable();
            $table->text('override_reason')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('investor_id');
            $table->index('review_status');
            $table->index('decision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_reviews');
    }
};