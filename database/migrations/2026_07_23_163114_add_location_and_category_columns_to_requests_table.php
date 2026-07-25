<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            // Add category if it doesn't already exist in your schema
            if (!Schema::hasColumn('requests', 'category')) {
                $table->string('category')->nullable()->default('General')->after('urgency');
            }

            // Add latitude and longitude columns
            if (!Schema::hasColumn('requests', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable(false)->after('category');
            }

            if (!Schema::hasColumn('requests', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable(false)->after('latitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('requests', 'category') ? 'category' : null,
                Schema::hasColumn('requests', 'latitude') ? 'latitude' : null,
                Schema::hasColumn('requests', 'longitude') ? 'longitude' : null,
            ]));
        });
    }
};