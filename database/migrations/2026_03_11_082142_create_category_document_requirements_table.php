<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_document_requirements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('investor_category_id')->constrained('investor_categories')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();

            $table->boolean('is_required')->default(true);
            $table->boolean('is_multiple_allowed')->default(false);
            $table->unsignedInteger('minimum_required_count')->nullable();

            $table->boolean('requires_verification')->default(true);
            $table->boolean('is_visible_on_onboarding')->default(true);

            $table->boolean('applies_to_resident')->nullable();
            $table->boolean('applies_to_non_resident')->nullable();
            $table->boolean('applies_to_minor')->nullable();
            $table->boolean('applies_to_joint')->nullable();
            $table->boolean('applies_to_corporate')->nullable();

            $table->text('notes')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['investor_category_id', 'document_type_id'],
                'cdr_category_document_unique'
            );

            $table->index('is_required');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_document_requirements');
    }
};