<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_holidays', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->string('country_code', 10)->default('TZ');
            $table->string('status')->default('active'); // active, inactive
            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['holiday_date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_holidays');
    }
};