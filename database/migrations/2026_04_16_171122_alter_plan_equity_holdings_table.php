<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_equity_holdings', function (Blueprint $table) {

            if (!Schema::hasColumn('plan_equity_holdings', 'quantity')) {
                $table->decimal('quantity', 20, 6)->default(0);
            }

            if (!Schema::hasColumn('plan_equity_holdings', 'invested_amount')) {
                $table->decimal('invested_amount', 20, 2)->default(0);
            }

            if (!Schema::hasColumn('plan_equity_holdings', 'average_cost_per_share')) {
                $table->decimal('average_cost_per_share', 20, 6)->nullable();
            }
        });
    }

    public function down(): void {}
};