<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->string('fund_type')->default('unit_trust'); // unit_trust
            $table->string('pricing_method')->default('nav');   // nav
            $table->string('status')->default('draft');         // draft, approved, active, inactive

            $table->string('currency', 10)->default('TZS');
            $table->boolean('is_open_ended')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('fund_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funds');
    }
};