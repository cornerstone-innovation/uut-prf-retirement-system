<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('investor_onboarding_sessions', function (Blueprint $table) {
            $table->string('document_type')->nullable()->after('nida_number');
            $table->string('document_number')->nullable()->after('document_type');
            $table->string('document_original_name')->nullable()->after('document_number');
            $table->string('document_mime_type')->nullable()->after('document_original_name');
            $table->string('document_storage_disk')->nullable()->after('document_mime_type');
            $table->string('document_storage_path')->nullable()->after('document_storage_disk');
        });
    }

    public function down(): void
    {
        Schema::table('investor_onboarding_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'document_number',
                'document_original_name',
                'document_mime_type',
                'document_storage_disk',
                'document_storage_path',
            ]);
        });
    }
};