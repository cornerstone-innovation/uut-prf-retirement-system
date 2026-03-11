<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();

            $table->string('allowed_extensions')->nullable();
            $table->unsignedInteger('max_file_size_kb')->nullable();

            $table->boolean('requires_expiry_date')->default(false);
            $table->boolean('requires_issue_date')->default(false);
            $table->boolean('requires_document_number')->default(false);
            $table->boolean('requires_manual_verification')->default(true);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};