<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->foreignId('investor_kyc_profile_id')->nullable()->constrained('investor_kyc_profiles')->nullOnDelete();
            $table->foreignId('investor_category_id')->nullable()->constrained('investor_categories')->nullOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();

            $table->string('original_filename');
            $table->string('stored_filename')->nullable();
            $table->string('storage_disk')->default('s3');
            $table->string('storage_path');

            $table->string('mime_type')->nullable();
            $table->string('file_extension')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();

            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->string('verification_status')->default('pending');
            $table->text('verification_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('verification_status');
            $table->index('uploaded_at');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_documents');
    }
};