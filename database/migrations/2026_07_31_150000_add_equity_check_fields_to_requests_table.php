<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (! Schema::hasColumn('requests', 'detected_language')) {
                $table->string('detected_language', 40)->nullable()->after('source_channel');
            }
            if (! Schema::hasColumn('requests', 'equity_flag')) {
                $table->boolean('equity_flag')->default(false)->after('detected_language');
            }
            if (! Schema::hasColumn('requests', 'equity_reasons')) {
                $table->json('equity_reasons')->nullable()->after('equity_flag');
            }
            if (! Schema::hasColumn('requests', 'equity_boost')) {
                $table->unsignedTinyInteger('equity_boost')->default(0)->after('equity_reasons');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $cols = array_values(array_filter([
                Schema::hasColumn('requests', 'detected_language') ? 'detected_language' : null,
                Schema::hasColumn('requests', 'equity_flag') ? 'equity_flag' : null,
                Schema::hasColumn('requests', 'equity_reasons') ? 'equity_reasons' : null,
                Schema::hasColumn('requests', 'equity_boost') ? 'equity_boost' : null,
            ]));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
