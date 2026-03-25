<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kyc_tier_rules')) {
            Schema::create('kyc_tier_rules', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();

                $table->foreignId('kyc_tier_id')->constrained('kyc_tiers')->cascadeOnDelete();

                $table->boolean('requires_phone_verified')->default(false);
                $table->boolean('requires_nida_verified')->default(false);
                $table->boolean('requires_identity_verified')->default(false);
                $table->boolean('requires_profile_completed')->default(false);
                $table->boolean('requires_documents_completed')->default(false);
                $table->boolean('requires_admin_approval')->default(false);

                $table->boolean('is_active')->default(true);

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_tier_rules');
    }
};