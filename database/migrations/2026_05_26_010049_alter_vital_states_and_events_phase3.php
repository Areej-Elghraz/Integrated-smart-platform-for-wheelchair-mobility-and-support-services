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
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            // current_vital_states
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE current_vital_states MODIFY COLUMN heart_rate_status ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE current_vital_states MODIFY COLUMN temperature_status ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE current_vital_states MODIFY COLUMN fall_status ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE current_vital_states MODIFY COLUMN risk_level ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");

            // events
            // 1. Expand enum to include old and new values
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE events MODIFY COLUMN severity ENUM('low', 'medium', 'high', 'normal', 'critical') NOT NULL DEFAULT 'normal'");
            
            // 2. Update existing data
            \Illuminate\Support\Facades\DB::statement("UPDATE events SET severity = 'normal' WHERE severity = 'low'");
            \Illuminate\Support\Facades\DB::statement("UPDATE events SET severity = 'critical' WHERE severity = 'high'");

            // 3. Restrict enum to new values only
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE events MODIFY COLUMN severity ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
        }
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE current_vital_states MODIFY COLUMN heart_rate_status ENUM('normal', 'medium', 'warning') NOT NULL DEFAULT 'normal'");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE current_vital_states MODIFY COLUMN temperature_status ENUM('normal', 'medium', 'warning') NOT NULL DEFAULT 'normal'");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE current_vital_states MODIFY COLUMN fall_status TINYINT(1) NOT NULL DEFAULT 0");
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE current_vital_states MODIFY COLUMN risk_level ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'low'");

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE events MODIFY COLUMN severity ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'low'");
    }
};
