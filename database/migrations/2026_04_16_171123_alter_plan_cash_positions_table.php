<?php

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_cash_positions', function (Blueprint $table) {

            if (!Schema::hasColumn('plan_cash_positions', 'cash_amount')) {
                $table->decimal('cash_amount', 20, 2)->default(0);
            }

            if (!Schema::hasColumn('plan_cash_positions', 'source_type')) {
                $table->string('source_type')->default('manual');
            }
        });
    }

    public function down(): void {}
};