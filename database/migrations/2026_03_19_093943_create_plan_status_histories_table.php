<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_status_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');

            $table->text('notes')->nullable();

            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('plan_id');
            $table->index('to_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_status_histories');
    }
};