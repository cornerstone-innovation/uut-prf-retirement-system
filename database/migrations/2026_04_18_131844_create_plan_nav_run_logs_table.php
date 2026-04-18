<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_nav_run_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->date('valuation_date');
            $table->dateTime('executed_at')->nullable();
            $table->string('status', 50)->default('completed');
            $table->text('message')->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->unique(['plan_id', 'valuation_date'], 'plan_nav_run_logs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_nav_run_logs');
    }
};