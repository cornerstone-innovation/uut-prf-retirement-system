<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('market_security_price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('market_security_id')
                ->constrained('market_securities')
                ->cascadeOnDelete();

            $table->date('price_date');
            $table->dateTime('captured_at')->nullable();

            $table->decimal('market_price', 20, 6)->default(0);
            $table->decimal('opening_price', 20, 6)->nullable();
            $table->decimal('change_amount', 20, 6)->nullable();
            $table->decimal('percentage_change', 20, 6)->nullable();
            $table->decimal('high_price', 20, 6)->nullable();
            $table->decimal('low_price', 20, 6)->nullable();
            $table->bigInteger('volume')->nullable();

            $table->string('source', 50)->default('dse');
            $table->jsonb('raw_payload')->nullable();

            $table->timestamps();

            $table->unique(['market_security_id', 'price_date']);
            $table->index(['price_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_security_price_snapshots');
    }
};