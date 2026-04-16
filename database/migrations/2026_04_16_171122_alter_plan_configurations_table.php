<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_configurations', function (Blueprint $table) {

            if (!Schema::hasColumn('plan_configurations', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }

            if (!Schema::hasColumn('plan_configurations', 'plan_family')) {
                $table->string('plan_family')->nullable();
            }

            if (!Schema::hasColumn('plan_configurations', 'valuation_method')) {
                $table->string('valuation_method')->nullable();
            }

            if (!Schema::hasColumn('plan_configurations', 'phase_status')) {
                $table->string('phase_status')->default('initial_offer');
            }

            if (!Schema::hasColumn('plan_configurations', 'market_close_time')) {
                $table->time('market_close_time')->nullable();
            }

            if (!Schema::hasColumn('plan_configurations', 'auto_calculate_nav')) {
                $table->boolean('auto_calculate_nav')->default(true);
            }
        });
    }

    public function down(): void {}
};
