<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();

            $table->decimal('amount', 18, 2);
            $table->string('currency', 10)->default('TZS');

            $table->string('request_type')->default('initial'); // initial, additional, sip
            $table->string('status')->default('pending_payment'); // pending_payment, payment_received, processing, completed, failed, cancelled

            $table->string('kyc_tier_at_request')->nullable();
            $table->boolean('is_sip')->default(false);

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['investor_id', 'status']);
            $table->index(['plan_id', 'status']);
            $table->index('request_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};