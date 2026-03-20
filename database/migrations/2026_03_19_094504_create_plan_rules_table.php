<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();

            $table->decimal('minimum_initial_investment', 18, 2)->nullable();
            $table->decimal('minimum_additional_investment', 18, 2)->nullable();
            $table->decimal('minimum_redemption_amount', 18, 2)->nullable();
            $table->decimal('minimum_balance_after_redemption', 18, 2)->nullable();
            $table->decimal('maximum_initial_investment', 18, 2)->nullable();
            $table->decimal('maximum_additional_investment', 18, 2)->nullable();

            // Exit rules
            $table->decimal('exit_fee_percentage', 5, 2)->nullable();
            $table->unsignedInteger('exit_fee_period_days')->nullable();

            // SIP
            $table->string('sip_frequency')->nullable();

            // Currency
            $table->string('currency')->default('TZS');

            // Optional: status upgrade
            $table->string('status')->default('active');

            $table->unsignedInteger('lock_in_period_years')->default(0);

            $table->boolean('switching_allowed')->default(false);
            $table->boolean('sip_allowed')->default(false);
            $table->decimal('minimum_sip_amount', 18, 2)->nullable();

            $table->boolean('option_growth')->default(true);
            $table->boolean('option_dividend')->default(false);
            $table->boolean('option_dividend_reinvestment')->default(false);

            $table->boolean('is_active')->default(true);

            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['plan_id', 'is_active']);
        });
    }


    
    public function down(): void
    {
        Schema::dropIfExists('plan_rules');
    }
};