<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_equity_holdings', function (Blueprint $table) {
            if (! Schema::hasColumn('plan_equity_holdings', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }

            if (! Schema::hasColumn('plan_equity_holdings', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            }

            if (! Schema::hasColumn('plan_equity_holdings', 'market_security_id')) {
                $table->foreignId('market_security_id')->nullable()->constrained('market_securities')->nullOnDelete();
            }

            if (! Schema::hasColumn('plan_equity_holdings', 'quantity')) {
                $table->decimal('quantity', 20, 6)->default(0);
            }

            if (! Schema::hasColumn('plan_equity_holdings', 'invested_amount')) {
                $table->decimal('invested_amount', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_equity_holdings', 'average_cost_per_share')) {
                $table->decimal('average_cost_per_share', 20, 6)->nullable();
            }

            if (! Schema::hasColumn('plan_equity_holdings', 'trade_date')) {
                $table->date('trade_date')->nullable();
            }

            if (! Schema::hasColumn('plan_equity_holdings', 'holding_status')) {
                $table->string('holding_status')->default('active');
            }

            if (! Schema::hasColumn('plan_equity_holdings', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        //
    }
};