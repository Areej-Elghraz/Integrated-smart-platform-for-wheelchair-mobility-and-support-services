<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Add resolved_at timestamp for deduplication
            $table->timestamp('resolved_at')->nullable()->after('read_at');
        });

        // Modify the type enum to include sos and battery
        // MySQL requires raw ALTER for enum changes
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE events MODIFY COLUMN type ENUM('health', 'obstacle', 'sos', 'battery') NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('resolved_at');
        });

        DB::statement("ALTER TABLE events MODIFY COLUMN type ENUM('health', 'obstacle') NOT NULL");
    }
};
