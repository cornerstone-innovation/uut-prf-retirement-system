<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_valuation_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();

            $table->date('valuation_date');
            $table->string('plan_family', 50)->nullable();

            $table->decimal('equity_market_value', 20, 2)->default(0);
            $table->decimal('bond_market_value', 20, 2)->default(0);
            $table->decimal('bond_accrued_interest', 20, 2)->default(0);
            $table->decimal('cash_value', 20, 2)->default(0);

            $table->decimal('total_gross_asset_value', 20, 2)->default(0);
            $table->decimal('total_liabilities', 20, 2)->default(0);
            $table->decimal('net_asset_value', 20, 2)->default(0);

            $table->decimal('outstanding_units', 20, 6)->default(0);
            $table->decimal('nav_per_unit', 20, 6)->default(0);

            $table->string('price_source', 50)->nullable();
            $table->string('calculation_status', 50)->default('calculated');
            $table->text('notes')->nullable();

            $table->jsonb('breakdown')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['plan_id', 'valuation_date']);
            $table->index(['plan_id', 'valuation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_valuation_snapshots');
    }
};