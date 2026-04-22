<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_configurations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();

            $table->string('plan_family')->nullable(); 
            // youngsters | middle_age | seniors

            $table->string('valuation_method')->nullable(); 
            // fixed_offer_then_equity | fixed_offer_then_equity_bonds | fixed_offer_then_bonds

            $table->date('initial_offer_start_date')->nullable();
            $table->date('initial_offer_end_date')->nullable();
            $table->integer('initial_offer_duration_days')->nullable();
            $table->decimal('initial_offer_price_per_unit', 20, 6)->nullable();

            $table->string('phase_status')->default('initial_offer');
            // initial_offer | live_nav | suspended | closed

            $table->timestamp('live_phase_started_at')->nullable();

            $table->time('market_close_time')->nullable();
            $table->string('market_close_timezone')->default('Africa/Dar_es_Salaam');

            $table->boolean('auto_calculate_nav')->default(true);
            $table->boolean('allow_nav_override')->default(true);
            $table->boolean('allow_phase_override')->default(true);

            $table->boolean('is_phase_overridden')->default(false);
            $table->text('phase_override_reason')->nullable();
            $table->foreignId('phase_override_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('phase_override_at')->nullable();

            $table->timestamps();

            $table->unique('plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_configurations');
    }
};