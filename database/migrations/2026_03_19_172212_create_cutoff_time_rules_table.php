<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutoff_time_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete(); // null = global fallback

            $table->time('cutoff_time');
            $table->string('timezone')->default('Africa/Dar_es_Salaam');

            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->string('status')->default('draft'); // draft, approved, active, inactive
            $table->boolean('is_active')->default(false);

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['plan_id', 'is_active']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutoff_time_rules');
    }
};