<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_cash_positions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();

            $table->date('position_date')->index();
            $table->decimal('cash_amount', 20, 2)->default(0);

            $table->string('source_type')->default('manual');
            // manual | initial_balance | dividend_retained | coupon_received | purchase_inflow | redemption_outflow | rebalance

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_cash_positions');
    }
};