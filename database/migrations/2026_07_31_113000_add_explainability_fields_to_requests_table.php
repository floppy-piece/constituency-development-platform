<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (! Schema::hasColumn('requests', 'urgency_score')) {
                $table->unsignedTinyInteger('urgency_score')->nullable()->after('urgency');
            }

            if (! Schema::hasColumn('requests', 'evaluation_thoughts')) {
                $table->text('evaluation_thoughts')->nullable()->after('confidence');
            }

            if (! Schema::hasColumn('requests', 'suggested_fix')) {
                $table->text('suggested_fix')->nullable()->after('evaluation_thoughts');
            }

            if (! Schema::hasColumn('requests', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('requests', 'urgency_score') ? 'urgency_score' : null,
                Schema::hasColumn('requests', 'evaluation_thoughts') ? 'evaluation_thoughts' : null,
                Schema::hasColumn('requests', 'suggested_fix') ? 'suggested_fix' : null,
                Schema::hasColumn('requests', 'resolved_at') ? 'resolved_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
