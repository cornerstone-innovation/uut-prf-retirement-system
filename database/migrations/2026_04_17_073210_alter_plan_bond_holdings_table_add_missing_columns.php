<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_bond_holdings', function (Blueprint $table) {
            if (! Schema::hasColumn('plan_bond_holdings', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'bond_name')) {
                $table->string('bond_name')->nullable();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'bond_code')) {
                $table->string('bond_code')->nullable();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'principal_amount')) {
                $table->decimal('principal_amount', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'coupon_rate_percent')) {
                $table->decimal('coupon_rate_percent', 10, 4)->default(0);
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'issue_date')) {
                $table->date('issue_date')->nullable();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'investment_date')) {
                $table->date('investment_date')->nullable();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'maturity_date')) {
                $table->date('maturity_date')->nullable();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'coupon_frequency')) {
                $table->string('coupon_frequency')->nullable();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'last_coupon_date')) {
                $table->date('last_coupon_date')->nullable();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'next_coupon_date')) {
                $table->date('next_coupon_date')->nullable();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'accrued_interest_amount')) {
                $table->decimal('accrued_interest_amount', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'face_value')) {
                $table->decimal('face_value', 20, 2)->nullable();
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'holding_status')) {
                $table->string('holding_status')->default('active');
            }

            if (! Schema::hasColumn('plan_bond_holdings', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        //
    }
};