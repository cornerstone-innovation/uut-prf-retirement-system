<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();

            $table->string('email')->nullable();
            $table->string('phone_primary')->nullable();
            $table->string('phone_secondary')->nullable();

            $table->string('alternate_contact_name')->nullable();
            $table->string('alternate_contact_phone')->nullable();

            $table->string('preferred_contact_method')->nullable(); // email, phone, sms, whatsapp

            $table->timestamps();

            $table->unique('investor_id');
            $table->index('email');
            $table->index('phone_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_contacts');
    }
};