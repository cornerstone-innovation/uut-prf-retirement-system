<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // new_phase
        if (!Schema::hasColumn('plan_phase_override_logs', 'new_phase')) {
            Schema::table('plan_phase_override_logs', function (Blueprint $table) {
                $table->string('new_phase')->nullable()->after('previous_phase');
            });
        }

        // previous_phase
        if (!Schema::hasColumn('plan_phase_override_logs', 'previous_phase')) {
            Schema::table('plan_phase_override_logs', function (Blueprint $table) {
                $table->string('previous_phase')->nullable();
            });
        }

        // override_reason
        if (!Schema::hasColumn('plan_phase_override_logs', 'override_reason')) {
            Schema::table('plan_phase_override_logs', function (Blueprint $table) {
                $table->text('override_reason')->nullable();
            });
        }

        // override_by
        if (!Schema::hasColumn('plan_phase_override_logs', 'override_by')) {
            Schema::table('plan_phase_override_logs', function (Blueprint $table) {
                $table->foreignId('override_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        // effective_at
        if (!Schema::hasColumn('plan_phase_override_logs', 'effective_at')) {
            Schema::table('plan_phase_override_logs', function (Blueprint $table) {
                $table->timestamp('effective_at')->nullable();
            });
        }

        // previous_offer_end_date
        if (!Schema::hasColumn('plan_phase_override_logs', 'previous_offer_end_date')) {
            Schema::table('plan_phase_override_logs', function (Blueprint $table) {
                $table->date('previous_offer_end_date')->nullable();
            });
        }

        // new_offer_end_date
        if (!Schema::hasColumn('plan_phase_override_logs', 'new_offer_end_date')) {
            Schema::table('plan_phase_override_logs', function (Blueprint $table) {
                $table->date('new_offer_end_date')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('plan_phase_override_logs', function (Blueprint $table) {

            if (Schema::hasColumn('plan_phase_override_logs', 'new_phase')) {
                $table->dropColumn('new_phase');
            }

            if (Schema::hasColumn('plan_phase_override_logs', 'previous_phase')) {
                $table->dropColumn('previous_phase');
            }

            if (Schema::hasColumn('plan_phase_override_logs', 'override_reason')) {
                $table->dropColumn('override_reason');
            }

            if (Schema::hasColumn('plan_phase_override_logs', 'effective_at')) {
                $table->dropColumn('effective_at');
            }

            if (Schema::hasColumn('plan_phase_override_logs', 'previous_offer_end_date')) {
                $table->dropColumn('previous_offer_end_date');
            }

            if (Schema::hasColumn('plan_phase_override_logs', 'new_offer_end_date')) {
                $table->dropColumn('new_offer_end_date');
            }

            // foreign key drop (safe)
            if (Schema::hasColumn('plan_phase_override_logs', 'override_by')) {
                $table->dropForeign(['override_by']);
                $table->dropColumn('override_by');
            }
        });
    }
};