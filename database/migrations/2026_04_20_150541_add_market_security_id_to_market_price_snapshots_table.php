<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('market_price_snapshots', function (Blueprint $table) {
            $table->foreignId('market_security_id')
                ->nullable()
                ->after('updated_at')
                ->constrained('market_securities')
                ->nullOnDelete();

            $table->index(['market_security_id', 'valuation_date']);
        });
    }

    public function down(): void
    {
        Schema::table('market_price_snapshots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('market_security_id');
        });
    }
};