<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_bond_holdings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();

            $table->string('bond_name');
            $table->string('bond_code')->nullable()->index();

            $table->decimal('principal_amount', 20, 2)->default(0);
            $table->decimal('coupon_rate_percent', 10, 6)->default(0);

            $table->date('issue_date')->nullable();
            $table->date('investment_date')->nullable();
            $table->date('maturity_date')->nullable();

            $table->string('coupon_frequency')->default('annual');
            // annual | semiannual | quarterly | monthly

            $table->date('last_coupon_date')->nullable();
            $table->date('next_coupon_date')->nullable();

            $table->decimal('accrued_interest_amount', 20, 6)->default(0);
            $table->decimal('face_value', 20, 2)->nullable();

            $table->string('holding_status')->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_bond_holdings');
    }
};