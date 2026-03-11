<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('approval_type');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_reference')->nullable();

            $table->string('status')->default('pending');

            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_approver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->text('decision_reason')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('approval_type');
            $table->index('entity_type');
            $table->index('entity_id');
            $table->index('entity_reference');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};