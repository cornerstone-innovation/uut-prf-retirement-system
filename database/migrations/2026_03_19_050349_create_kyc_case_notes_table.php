<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_case_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('investor_id')
                ->constrained('investors')
                ->cascadeOnDelete();

            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('note');
            $table->string('note_type')->default('internal'); // internal, escalation, review, handoff
            $table->boolean('is_pinned')->default(false);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['investor_id', 'note_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_case_notes');
    }
};