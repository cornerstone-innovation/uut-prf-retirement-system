<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nav_override_logs', function (Blueprint $table) {

            if (!Schema::hasColumn('nav_override_logs', 'override_nav_per_unit')) {
                $table->decimal('override_nav_per_unit', 20, 6)->nullable();
            }

            if (!Schema::hasColumn('nav_override_logs', 'override_reason')) {
                $table->text('override_reason')->nullable();
            }
        });
    }

    public function down(): void {}
};