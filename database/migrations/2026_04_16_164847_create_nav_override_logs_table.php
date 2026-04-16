<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_override_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('nav_record_id')->constrained('nav_records')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();

            $table->decimal('calculated_nav_per_unit', 20, 6);
            $table->decimal('override_nav_per_unit', 20, 6);

            $table->text('override_reason');
            $table->foreignId('override_by')->constrained('users')->cascadeOnDelete();

            $table->timestamp('override_at')->nullable();

            $table->json('calculation_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_override_logs');
    }
};