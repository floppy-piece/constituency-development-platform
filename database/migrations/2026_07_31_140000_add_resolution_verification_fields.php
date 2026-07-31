<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            if (! Schema::hasColumn('requests', 'source_channel')) {
                $table->string('source_channel', 20)->nullable()->after('file_type');
            }
            if (! Schema::hasColumn('requests', 'verification_status')) {
                $table->string('verification_status', 20)->nullable()->after('resolved_at');
            }
            if (! Schema::hasColumn('requests', 'verification_requested_at')) {
                $table->timestamp('verification_requested_at')->nullable()->after('verification_status');
            }
            if (! Schema::hasColumn('requests', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_requested_at');
            }
            if (! Schema::hasColumn('requests', 'verification_note')) {
                $table->text('verification_note')->nullable()->after('verified_at');
            }
            if (! Schema::hasColumn('requests', 'verification_file_path')) {
                $table->string('verification_file_path')->nullable()->after('verification_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $cols = array_values(array_filter([
                Schema::hasColumn('requests', 'source_channel') ? 'source_channel' : null,
                Schema::hasColumn('requests', 'verification_status') ? 'verification_status' : null,
                Schema::hasColumn('requests', 'verification_requested_at') ? 'verification_requested_at' : null,
                Schema::hasColumn('requests', 'verified_at') ? 'verified_at' : null,
                Schema::hasColumn('requests', 'verification_note') ? 'verification_note' : null,
                Schema::hasColumn('requests', 'verification_file_path') ? 'verification_file_path' : null,
            ]));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
