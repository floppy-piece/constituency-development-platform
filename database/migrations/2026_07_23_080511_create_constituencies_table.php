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
        Schema::create('constituencies', function (Blueprint $table) {
            $table->id('constituency_id');
            $table->string('name')->unique();
            $table->foreignId('mp_id')->nullable(false)->constrained('mps', 'mp_id')->onDelete('cascade');
            $table->string('code')->nullable();
            $table->string('county_or_region')->nullable();
            $table->decimal('latitude', 10, 8)->nullable(false);
            $table->decimal('longitude', 11, 8)->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('constituencies');
    }
};