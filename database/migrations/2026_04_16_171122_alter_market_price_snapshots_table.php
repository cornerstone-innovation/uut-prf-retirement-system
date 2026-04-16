<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('market_price_snapshots', function (Blueprint $table) {

            if (!Schema::hasColumn('market_price_snapshots', 'market_price')) {
                $table->decimal('market_price', 20, 6)->nullable();
            }

            if (!Schema::hasColumn('market_price_snapshots', 'valuation_date')) {
                $table->date('valuation_date')->nullable();
            }

            if (!Schema::hasColumn('market_price_snapshots', 'raw_payload')) {
                $table->json('raw_payload')->nullable();
            }
        });
    }

    public function down(): void {}
};