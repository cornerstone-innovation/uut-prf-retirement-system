<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('fund_id')->constrained('funds')->cascadeOnDelete();
            $table->foreignId('plan_category_id')->nullable()->constrained('plan_categories')->nullOnDelete();

            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->string('status')->default('draft'); // draft, pending_approval, approved, active, inactive
            $table->boolean('is_default')->default(false);

            $table->string('investment_objective')->nullable();
            $table->string('target_audience')->nullable();

            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            

            $table->timestamps();

            $table->index('status');
            $table->index('fund_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};