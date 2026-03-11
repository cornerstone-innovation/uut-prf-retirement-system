<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('requires_guardian')->default(false);
            $table->boolean('requires_custodian')->default(false);
            $table->boolean('requires_ubo')->default(false);
            $table->boolean('requires_authorized_signatories')->default(false);
            $table->boolean('allows_joint_holding')->default(false);
            $table->boolean('is_minor_category')->default(false);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_categories');
    }
};