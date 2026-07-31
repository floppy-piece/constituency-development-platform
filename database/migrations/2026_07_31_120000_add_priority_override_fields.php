<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (! Schema::hasColumn('requests', 'priority_factors')) {
                $table->json('priority_factors')->nullable()->after('priority_score');
            }

            if (! Schema::hasColumn('requests', 'mp_priority_rank')) {
                $table->unsignedInteger('mp_priority_rank')->nullable()->after('priority_factors');
            }

            if (! Schema::hasColumn('requests', 'override_reason')) {
                $table->text('override_reason')->nullable()->after('mp_priority_rank');
            }

            if (! Schema::hasColumn('requests', 'overridden_by')) {
                $table->unsignedBigInteger('overridden_by')->nullable()->after('override_reason');
            }

            if (! Schema::hasColumn('requests', 'overridden_at')) {
                $table->timestamp('overridden_at')->nullable()->after('overridden_by');
            }
        });

        Schema::table('mps', function (Blueprint $table) {
            if (! Schema::hasColumn('mps', 'priorities_locked_at')) {
                $table->timestamp('priorities_locked_at')->nullable()->after('avatar_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('requests', 'priority_factors') ? 'priority_factors' : null,
                Schema::hasColumn('requests', 'mp_priority_rank') ? 'mp_priority_rank' : null,
                Schema::hasColumn('requests', 'override_reason') ? 'override_reason' : null,
                Schema::hasColumn('requests', 'overridden_by') ? 'overridden_by' : null,
                Schema::hasColumn('requests', 'overridden_at') ? 'overridden_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('mps', function (Blueprint $table) {
            if (Schema::hasColumn('mps', 'priorities_locked_at')) {
                $table->dropColumn('priorities_locked_at');
            }
        });
    }
};
