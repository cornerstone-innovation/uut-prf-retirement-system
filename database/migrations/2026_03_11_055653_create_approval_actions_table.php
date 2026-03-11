<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();

            $table->string('action');
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('comments')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};