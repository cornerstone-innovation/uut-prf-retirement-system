<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_cash_positions', function (Blueprint $table) {
            if (! Schema::hasColumn('plan_cash_positions', 'uuid')) {
                $table->uuid('uuid')->nullable()->after('id');
            }

            if (! Schema::hasColumn('plan_cash_positions', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            }

            if (! Schema::hasColumn('plan_cash_positions', 'position_date')) {
                $table->date('position_date')->nullable();
            }

            if (! Schema::hasColumn('plan_cash_positions', 'cash_amount')) {
                $table->decimal('cash_amount', 20, 2)->default(0);
            }

            if (! Schema::hasColumn('plan_cash_positions', 'source_type')) {
                $table->string('source_type')->nullable();
            }

            if (! Schema::hasColumn('plan_cash_positions', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (! Schema::hasColumn('plan_cash_positions', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        //
    }
};