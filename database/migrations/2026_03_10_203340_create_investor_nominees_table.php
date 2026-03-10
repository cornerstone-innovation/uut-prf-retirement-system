<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();

            $table->string('full_name');
            $table->string('relationship');
            $table->date('date_of_birth')->nullable();

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('national_id_number')->nullable();

            $table->decimal('allocation_percentage', 5, 2)->default(0.00);

            $table->boolean('is_minor')->default(false);
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();

            $table->text('address')->nullable();

            $table->timestamps();

            $table->index('relationship');
            $table->index('is_minor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_nominees');
    }
};