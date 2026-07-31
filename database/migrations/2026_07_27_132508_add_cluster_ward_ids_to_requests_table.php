<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'cluster_ward_ids')) {
                $table->json('cluster_ward_ids')->nullable()->after('similar_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'cluster_ward_ids')) {
                $table->dropColumn('cluster_ward_ids');
            }
        });
    }
};

