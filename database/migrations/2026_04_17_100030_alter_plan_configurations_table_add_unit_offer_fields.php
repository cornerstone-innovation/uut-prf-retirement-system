<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plan_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('plan_configurations', 'total_units_on_offer')) {
                $table->decimal('total_units_on_offer', 20, 6)->nullable()->after('phase_status');
            }

            if (! Schema::hasColumn('plan_configurations', 'initial_offer_price')) {
                $table->decimal('initial_offer_price', 20, 6)->nullable()->after('total_units_on_offer');
            }

            if (! Schema::hasColumn('plan_configurations', 'initial_offer_start_date')) {
                $table->date('initial_offer_start_date')->nullable()->after('initial_offer_price');
            }

            if (! Schema::hasColumn('plan_configurations', 'initial_offer_end_date')) {
                $table->date('initial_offer_end_date')->nullable()->after('initial_offer_start_date');
            }

            if (! Schema::hasColumn('plan_configurations', 'allow_post_offer_sales')) {
                $table->boolean('allow_post_offer_sales')->default(true)->after('initial_offer_end_date');
            }

            if (! Schema::hasColumn('plan_configurations', 'unit_sale_cap_type')) {
                $table->string('unit_sale_cap_type', 50)->default('fixed_cap')->after('allow_post_offer_sales');
            }
        });
    }

    public function down(): void
    {
        //
    }
};