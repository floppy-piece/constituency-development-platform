<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_clusters', function (Blueprint $table) {
            $table->id('cluster_id');
            $table->foreignId('mp_id')->constrained('mps', 'mp_id')->cascadeOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained('wards', 'ward_id')->nullOnDelete();
            $table->string('category')->default('General');
            $table->string('theme_label');
            $table->unsignedInteger('report_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('trend', 20)->default('stable'); // rising | stable | falling
            $table->decimal('centroid_lat', 10, 7)->nullable();
            $table->decimal('centroid_lng', 10, 7)->nullable();
            $table->unsignedTinyInteger('severity_score')->nullable();
            $table->json('ward_ids')->nullable();
            $table->timestamps();

            $table->index(['mp_id', 'category', 'ward_id']);
            $table->index(['mp_id', 'last_seen_at']);
        });

        Schema::table('requests', function (Blueprint $table) {
            if (! Schema::hasColumn('requests', 'cluster_id')) {
                $table->foreignId('cluster_id')
                    ->nullable()
                    ->after('ward_id')
                    ->constrained('issue_clusters', 'cluster_id')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'cluster_id')) {
                $table->dropConstrainedForeignId('cluster_id');
            }
        });

        Schema::dropIfExists('issue_clusters');
    }
};
