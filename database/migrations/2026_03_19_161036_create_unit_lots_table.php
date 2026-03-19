<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_lots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('investment_transaction_id')->constrained('investment_transactions')->cascadeOnDelete();

            $table->decimal('original_units', 18, 6);
            $table->decimal('remaining_units', 18, 6);
            $table->decimal('nav_per_unit', 18, 6);
            $table->decimal('gross_amount', 18, 2);

            $table->date('acquired_date')->nullable();
            $table->string('status')->default('active'); // active, partially_redeemed, fully_redeemed

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['investor_id', 'plan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_lots');
    }
};