<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add Demographics to Constituencies
        Schema::table('constituencies', function (Blueprint $table) {
            $table->unsignedInteger('total_population')->nullable();
            $table->decimal('poverty_rate_percentage', 5, 2)->default(0.00); // e.g. 45.50%
            $table->decimal('youth_unemployment_rate', 5, 2)->default(0.00);
            $table->integer('population_density_per_sqkm')->nullable();
        });

        // Add Local Development Plan (CIDP) tracking to Constituency Facilities
        Schema::table('constituency_facilities', function (Blueprint $table) {
            $table->boolean('is_in_cidp_plan')->default(false)->comment('Listed in County Integrated Development Plan');
            $table->string('cidp_priority_tier')->default('medium')->comment('high, medium, low');
            $table->decimal('poverty_index_score', 5, 2)->default(50.00)->comment('0-100 socio-economic deprivation score');
        });
    }

    public function down(): void
    {
        Schema::table('constituencies', function (Blueprint $table) {
            $table->dropColumn(['total_population', 'poverty_rate_percentage', 'youth_unemployment_rate', 'population_density_per_sqkm']);
        });

        Schema::table('constituency_facilities', function (Blueprint $table) {
            $table->dropColumn(['is_in_cidp_plan', 'cidp_priority_tier', 'poverty_index_score']);
        });
    }
};