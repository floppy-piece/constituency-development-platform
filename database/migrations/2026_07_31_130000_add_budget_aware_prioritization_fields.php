<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (! Schema::hasColumn('requests', 'estimated_cost_kes')) {
                $table->unsignedBigInteger('estimated_cost_kes')->nullable()->after('priority_factors');
            }
            if (! Schema::hasColumn('requests', 'cost_source')) {
                $table->string('cost_source', 20)->nullable()->after('estimated_cost_kes');
            }
            if (! Schema::hasColumn('requests', 'cost_rationale')) {
                $table->text('cost_rationale')->nullable()->after('cost_source');
            }
        });

        Schema::table('mps', function (Blueprint $table) {
            if (! Schema::hasColumn('mps', 'available_budget_kes')) {
                $table->unsignedBigInteger('available_budget_kes')->nullable()->after('priorities_locked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $cols = array_values(array_filter([
                Schema::hasColumn('requests', 'estimated_cost_kes') ? 'estimated_cost_kes' : null,
                Schema::hasColumn('requests', 'cost_source') ? 'cost_source' : null,
                Schema::hasColumn('requests', 'cost_rationale') ? 'cost_rationale' : null,
            ]));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('mps', function (Blueprint $table) {
            if (Schema::hasColumn('mps', 'available_budget_kes')) {
                $table->dropColumn('available_budget_kes');
            }
        });
    }
};
