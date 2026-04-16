<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_phase_override_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();

            $table->string('previous_phase')->nullable();
            $table->string('new_phase');

            $table->date('previous_offer_end_date')->nullable();
            $table->date('new_offer_end_date')->nullable();

            $table->timestamp('effective_at')->nullable();

            $table->text('override_reason');
            $table->foreignId('override_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_phase_override_logs');
    }
};