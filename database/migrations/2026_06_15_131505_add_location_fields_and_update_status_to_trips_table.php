<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->double('start_x')->nullable();
            $table->double('start_y')->nullable();
            $table->double('end_x')->nullable();
            $table->double('end_y')->nullable();
        });
        
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE trips MODIFY status ENUM('started', 'completed', 'failed') NOT NULL DEFAULT 'started'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE trips MODIFY status ENUM('started', 'completed') NOT NULL DEFAULT 'started'");
        }
        
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['start_x', 'start_y', 'end_x', 'end_y']);
        });
    }
};
