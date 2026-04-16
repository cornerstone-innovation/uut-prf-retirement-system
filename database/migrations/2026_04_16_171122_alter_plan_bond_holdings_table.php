<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_bond_holdings', function (Blueprint $table) {

            if (!Schema::hasColumn('plan_bond_holdings', 'coupon_rate_percent')) {
                $table->decimal('coupon_rate_percent', 10, 6)->default(0);
            }

            if (!Schema::hasColumn('plan_bond_holdings', 'accrued_interest_amount')) {
                $table->decimal('accrued_interest_amount', 20, 6)->default(0);
            }
        });
    }

    public function down(): void {}
};