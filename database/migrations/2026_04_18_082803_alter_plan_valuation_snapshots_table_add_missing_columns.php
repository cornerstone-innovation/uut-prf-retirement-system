<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_valuation_snapshots', function (Blueprint $table) {
            if (! Schema::hasColumn('plan_valuation_snapshots', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->constrained('plans')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'valuation_date')) {
                $table->date('valuation_date')->nullable();
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'plan_family')) {
                $table->string('plan_family', 50)->nullable();
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'equity_market_value')) {
                $table->decimal('equity_market_value', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'bond_market_value')) {
                $table->decimal('bond_market_value', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'bond_accrued_interest')) {
                $table->decimal('bond_accrued_interest', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'cash_value')) {
                $table->decimal('cash_value', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'total_gross_asset_value')) {
                $table->decimal('total_gross_asset_value', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'total_liabilities')) {
                $table->decimal('total_liabilities', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'net_asset_value')) {
                $table->decimal('net_asset_value', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'outstanding_units')) {
                $table->decimal('outstanding_units', 20, 6)->default(0);
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'nav_per_unit')) {
                $table->decimal('nav_per_unit', 20, 6)->default(0);
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'price_source')) {
                $table->string('price_source', 50)->nullable();
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'calculation_status')) {
                $table->string('calculation_status', 50)->default('calculated');
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'breakdown')) {
                $table->jsonb('breakdown')->nullable();
            }

            if (! Schema::hasColumn('plan_valuation_snapshots', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        //
    }
};