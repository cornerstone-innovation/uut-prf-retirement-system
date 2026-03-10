<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_kyc_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();

            $table->string('kyc_reference')->unique();

            $table->string('document_status')->default('incomplete');
            $table->string('identity_verification_status')->default('pending');
            $table->string('address_verification_status')->default('pending');
            $table->string('tax_verification_status')->default('pending');

            $table->string('pep_check_status')->nullable();
            $table->string('sanctions_check_status')->nullable();
            $table->string('aml_risk_level')->nullable();

            $table->text('review_notes')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->unique('investor_id');
            $table->index('document_status');
            $table->index('identity_verification_status');
            $table->index('address_verification_status');
            $table->index('tax_verification_status');
            $table->index('aml_risk_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_kyc_profiles');
    }
};