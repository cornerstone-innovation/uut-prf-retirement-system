<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->foreignId('investor_category_id')
                ->nullable()
                ->after('investor_type')
                ->constrained('investor_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('investor_category_id');
        });
    }
};