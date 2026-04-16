<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_securities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('source')->default('dse');
            $table->string('source_security_reference')->nullable()->index();

            $table->string('symbol')->index();
            $table->string('security_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('security_type')->nullable();
            $table->string('market_segment')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();

            $table->json('raw_payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_securities');
    }
};