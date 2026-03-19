<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();

            $table->date('valuation_date');
            $table->decimal('nav_per_unit', 18, 6);

            $table->string('status')->default('draft'); // draft, pending_approval, partially_approved, approved, published, rejected
            $table->string('source')->default('manual'); // manual, system

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('approved_by_1')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at_1')->nullable();

            $table->foreignId('approved_by_2')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at_2')->nullable();

            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['plan_id', 'valuation_date']);
            $table->index(['plan_id', 'status']);
            $table->index(['valuation_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_records');
    }
};