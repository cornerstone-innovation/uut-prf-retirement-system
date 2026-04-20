<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('market_price_snapshots', function (Blueprint $table) {
            $table->foreignId('market_security_id')
                ->after('id')
                ->nullable() // temporary to avoid breaking existing rows
                ->constrained('market_securities')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('market_price_snapshots', function (Blueprint $table) {
            $table->dropForeign(['market_security_id']);
            $table->dropColumn('market_security_id');
        });
    }
};