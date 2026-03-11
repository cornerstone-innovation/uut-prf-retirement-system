<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_directors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('investor_id')
                ->constrained('investors')
                ->cascadeOnDelete();

            $table->string('full_name');

            $table->string('national_id_number')->nullable();

            $table->string('passport_number')->nullable();

            $table->string('nationality')->nullable();

            $table->string('role')->nullable(); // Director, CEO, Signatory etc

            $table->boolean('has_signing_authority')->default(false);

            $table->decimal('ownership_percentage', 5, 2)->nullable();

            $table->string('identity_verification_status')
                ->default('pending'); // pending | verified | rejected

            $table->string('smile_verification_id')->nullable();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index('investor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_directors');
    }
};