<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();

            $table->string('address_type'); // residential, postal, business
            $table->string('country');
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('ward')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_address')->nullable();
            $table->string('postal_code')->nullable();

            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->index('address_type');
            $table->index('country');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_addresses');
    }
};