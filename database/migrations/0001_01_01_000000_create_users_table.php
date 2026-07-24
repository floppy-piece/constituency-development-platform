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

        Schema::create('mps', function (Blueprint $table) {
            $table->id('mp_id');
            $table->string('mp_name', 100);
            $table->string('constituency_name', 100);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->dateTime('term_start');
            $table->dateTime('term_end');
            $table->string('avatar_path')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('phone_number', 15)->unique();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_in_constituency')->default(false);
            $table->timestamp('last_request_at')->nullable();
            $table->timestamps();
        });
        

        Schema::create('requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('mp_id')->nullable()->constrained('mps', 'mp_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->text('raw_message')->nullable();
            $table->text('content')->nullable(); // Gemma 4 translated & analyzed output
            $table->string('upload_file_path')->nullable();
            $table->enum('file_type', ['text', 'image', 'audio'])->default('text');
            $table->enum('urgency', ['low', 'medium', 'high'])->default('medium');
            $table->string('category')->nullable(); // e.g. Roads, School, Lighting
            $table->unsignedInteger('similar_count')->default(1);
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('mp_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('requests');
        Schema::dropIfExists('mps');
    }
};
