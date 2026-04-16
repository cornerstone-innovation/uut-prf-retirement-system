<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_equity_holdings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_security_id')->constrained('market_securities')->cascadeOnDelete();

            $table->decimal('quantity', 20, 6)->default(0);
            $table->decimal('invested_amount', 20, 2)->default(0);
            $table->decimal('average_cost_per_share', 20, 6)->nullable();

            $table->date('trade_date')->nullable();
            $table->string('holding_status')->default('active');
            // active | closed

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'market_security_id'], 'uniq_plan_security_holding');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_equity_holdings');
    }
};