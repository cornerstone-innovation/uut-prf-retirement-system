<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_reference')->nullable();

            $table->json('metadata')->nullable();

            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index('action');
            $table->index('entity_type');
            $table->index('entity_id');
            $table->index('entity_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};