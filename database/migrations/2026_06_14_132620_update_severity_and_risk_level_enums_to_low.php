<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Step 1: Expand enums to include both 'normal' and 'low'
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN heart_rate_status ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN temperature_status ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN fall_status ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN risk_level ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
            DB::statement("ALTER TABLE events MODIFY COLUMN severity ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
        }

        // Step 2: Update existing 'normal' values to 'low'
        DB::table('ai_recommendations')->where('heart_rate_status', 'normal')->update(['heart_rate_status' => 'low']);
        DB::table('ai_recommendations')->where('temperature_status', 'normal')->update(['temperature_status' => 'low']);
        DB::table('ai_recommendations')->where('fall_status', 'normal')->update(['fall_status' => 'low']);
        DB::table('ai_recommendations')->where('risk_level', 'normal')->update(['risk_level' => 'low']);
        DB::table('events')->where('severity', 'normal')->update(['severity' => 'low']);

        if (DB::getDriverName() === 'mysql') {
            // Step 3: Shrink enums to only 'low', 'medium', 'critical'
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN heart_rate_status ENUM('low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN temperature_status ENUM('low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN fall_status ENUM('low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN risk_level ENUM('low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
            DB::statement("ALTER TABLE events MODIFY COLUMN severity ENUM('low', 'medium', 'critical') NOT NULL DEFAULT 'low'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Step 1: Expand enums to include both
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN heart_rate_status ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN temperature_status ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN fall_status ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN risk_level ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            DB::statement("ALTER TABLE events MODIFY COLUMN severity ENUM('normal', 'low', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
        }

        // Step 2: Revert 'low' back to 'normal'
        DB::table('ai_recommendations')->where('heart_rate_status', 'low')->update(['heart_rate_status' => 'normal']);
        DB::table('ai_recommendations')->where('temperature_status', 'low')->update(['temperature_status' => 'normal']);
        DB::table('ai_recommendations')->where('fall_status', 'low')->update(['fall_status' => 'normal']);
        DB::table('ai_recommendations')->where('risk_level', 'low')->update(['risk_level' => 'normal']);
        DB::table('events')->where('severity', 'low')->update(['severity' => 'normal']);

        if (DB::getDriverName() === 'mysql') {
            // Step 3: Shrink back to original
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN heart_rate_status ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN temperature_status ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN fall_status ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            DB::statement("ALTER TABLE ai_recommendations MODIFY COLUMN risk_level ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
            DB::statement("ALTER TABLE events MODIFY COLUMN severity ENUM('normal', 'medium', 'critical') NOT NULL DEFAULT 'normal'");
        }
    }
};
