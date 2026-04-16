<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('market_security_id')->constrained('market_securities')->cascadeOnDelete();

            $table->date('valuation_date')->index();
            $table->timestamp('captured_at')->nullable();

            $table->decimal('market_price', 20, 6)->nullable();
            $table->decimal('opening_price', 20, 6)->nullable();
            $table->decimal('high', 20, 6)->nullable();
            $table->decimal('low', 20, 6)->nullable();
            $table->decimal('change', 20, 6)->nullable();
            $table->decimal('percentage_change', 20, 6)->nullable();
            $table->bigInteger('volume')->nullable();

            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->unique(['market_security_id', 'valuation_date'], 'uniq_market_security_valuation_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_price_snapshots');
    }
};