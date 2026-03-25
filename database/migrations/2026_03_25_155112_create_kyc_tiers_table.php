<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_tiers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();

            $table->unsignedInteger('rank')->default(0);

            $table->boolean('can_view_products')->default(false);
            $table->boolean('can_purchase')->default(false);
            $table->boolean('can_redeem')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_tiers');
    }
};