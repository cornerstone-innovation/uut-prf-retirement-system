<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investor_kyc_profiles', function (Blueprint $table) {
            $table->string('kyc_tier')->default('tier_0')->after('kyc_reference');

            $table->timestamp('identity_verified_at')->nullable()->after('identity_verification_status');
            $table->timestamp('profile_completed_at')->nullable()->after('identity_verified_at');
            $table->timestamp('documents_completed_at')->nullable()->after('profile_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('investor_kyc_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'kyc_tier',
                'identity_verified_at',
                'profile_completed_at',
                'documents_completed_at',
            ]);
        });
    }
};