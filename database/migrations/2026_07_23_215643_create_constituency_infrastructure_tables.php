<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constituency_facilities', function (Blueprint $table) {
            $table->id('facility_id');
            $table->unsignedBigInteger('mp_id')->index();
            $table->string('facility_name'); // e.g. "Kilifi Primary School"
            $table->string('facility_type'); // e.g. "School", "Health Centre", "Vocational Centre"
            $table->string('location_name')->nullable();
            
            // Baseline metrics
            $table->integer('current_capacity')->default(0);
            $table->integer('current_enrollment')->default(0); // For schools/vocational centres
            $table->decimal('avg_travel_distance_km', 5, 2)->default(0.00); // Travel distance for citizens
            $table->decimal('capacity_deficit_percentage', 5, 2)->default(0.00); // Overcrowding %
            $table->integer('target_population_served')->default(0);
            
            $table->timestamps();
        });

        // Add impact score columns to the requests table
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'facility_id')) {
                $table->unsignedBigInteger('facility_id')->nullable()->after('mp_id');
            }
            if (!Schema::hasColumn('requests', 'priority_score')) {
                $table->decimal('priority_score', 5, 2)->default(0.00)->after('urgency');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constituency_facilities');
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['facility_id', 'priority_score']);
        });
    }
};