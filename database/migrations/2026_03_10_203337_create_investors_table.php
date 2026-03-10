<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('investor_number')->unique();
            $table->string('investor_type'); // individual, corporate, minor, joint

            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name');

            $table->string('company_name')->nullable();

            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();

            $table->string('national_id_number')->nullable();
            $table->string('tax_identification_number')->nullable();

            $table->string('onboarding_status')->default('draft');
            $table->string('kyc_status')->default('pending');
            $table->string('investor_status')->default('inactive');

            $table->string('risk_profile')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer_name')->nullable();
            $table->text('source_of_funds')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('investor_type');
            $table->index('onboarding_status');
            $table->index('kyc_status');
            $table->index('investor_status');
            $table->index('national_id_number');
            $table->index('tax_identification_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investors');
    }
};