<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('market_securities', function (Blueprint $table) {

            if (!Schema::hasColumn('market_securities', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique();
            }

            if (!Schema::hasColumn('market_securities', 'symbol')) {
                $table->string('symbol')->nullable()->index();
            }

            if (!Schema::hasColumn('market_securities', 'company_name')) {
                $table->string('company_name')->nullable();
            }

            if (!Schema::hasColumn('market_securities', 'raw_payload')) {
                $table->json('raw_payload')->nullable();
            }
        });
    }

    public function down(): void {}
};