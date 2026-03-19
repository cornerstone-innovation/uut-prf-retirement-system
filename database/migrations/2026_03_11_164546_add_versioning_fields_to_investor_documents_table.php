<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investor_documents', function (Blueprint $table) {
            $table->foreignId('parent_document_id')
                ->nullable()
                ->after('investor_category_id')
                ->constrained('investor_documents')
                ->nullOnDelete();

            $table->unsignedInteger('version_number')
                ->default(1)
                ->after('parent_document_id');

            $table->boolean('is_current_version')
                ->default(true)
                ->after('version_number');

            $table->timestamp('replaced_at')
                ->nullable()
                ->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('investor_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_document_id');
            $table->dropColumn([
                'version_number',
                'is_current_version',
                'replaced_at',
            ]);
        });
    }
};