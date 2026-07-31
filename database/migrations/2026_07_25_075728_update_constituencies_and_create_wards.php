<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update constituencies table
        Schema::table('constituencies', function (Blueprint $table) {
            $table->decimal('approximate_size', 10, 2)->nullable()->after('longitude');
        });

        // 2. Create wards table
        Schema::create('wards', function (Blueprint $table) {
            $table->id('ward_id');
            $table->unsignedBigInteger('constituency_id');
            $table->string('name', 255);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('approximate_size', 10, 2); // In square kilometers
            $table->timestamps();

            $table->foreign('constituency_id')
                  ->references('constituency_id')
                  ->on('constituencies')
                  ->onDelete('cascade');
        });

        // 3. Update requests table to track the ward
        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedBigInteger('ward_id')->nullable()->after('mp_id');
            $table->foreign('ward_id')
                  ->references('ward_id')
                  ->on('wards')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['ward_id']);
            $table->dropColumn('ward_id');
        });
        
        Schema::dropIfExists('wards');

        Schema::table('constituencies', function (Blueprint $table) {
            $table->dropColumn('approximate_size');
        });
    }
};