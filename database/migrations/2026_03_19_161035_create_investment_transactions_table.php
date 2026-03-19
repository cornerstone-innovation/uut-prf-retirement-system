<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();

            $table->string('transaction_type')->default('purchase'); // purchase, redemption, switch_in, switch_out
            $table->string('status')->default('completed');

            $table->decimal('gross_amount', 18, 2);
            $table->decimal('net_amount', 18, 2);
            $table->decimal('units', 18, 6);
            $table->decimal('nav_per_unit', 18, 6);

            $table->string('currency', 10)->default('TZS');
            $table->string('option')->nullable(); // growth, dividend, dividend_reinvestment

            $table->date('trade_date')->nullable();
            $table->date('pricing_date')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['investor_id', 'transaction_type']);
            $table->index(['plan_id', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_transactions');
    }
};