<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('market_data_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('sync_type', 100);
            $table->date('run_date');
            $table->string('timezone', 100)->nullable();
            $table->dateTime('executed_at')->nullable();

            $table->jsonb('metadata')->nullable();

            $table->timestamps();

            $table->unique(['sync_type', 'run_date'], 'market_data_sync_runs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_data_sync_runs');
    }
};