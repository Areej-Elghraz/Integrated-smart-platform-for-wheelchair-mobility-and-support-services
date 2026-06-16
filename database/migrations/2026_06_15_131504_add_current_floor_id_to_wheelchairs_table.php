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
        if (!Schema::hasColumn('wheelchairs', 'current_floor_id')) {
            Schema::table('wheelchairs', function (Blueprint $table) {
                $table->foreignId('current_floor_id')->nullable()->constrained('floors')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('wheelchairs', 'current_floor_id')) {
            Schema::table('wheelchairs', function (Blueprint $table) {
                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['current_floor_id']);
                }
                $table->dropColumn('current_floor_id');
            });
        }
    }
};
