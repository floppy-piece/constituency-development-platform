<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'whatsapp_linked_at')) {
                $table->timestamp('whatsapp_linked_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'last_latitude')) {
                $table->decimal('last_latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('users', 'last_longitude')) {
                $table->decimal('last_longitude', 11, 8)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_linked_at', 'last_latitude', 'last_longitude']);
        });
    }
};