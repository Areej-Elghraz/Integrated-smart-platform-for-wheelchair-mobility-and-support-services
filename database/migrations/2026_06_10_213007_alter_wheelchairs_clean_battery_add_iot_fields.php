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
        Schema::table('wheelchairs', function (Blueprint $table) {
            $table->dropColumn(['battery', 'voltage', 'current', 'temperature']);
            if (!Schema::hasColumn('wheelchairs', 'api_key')) {
                $table->string('api_key')->nullable()->unique()->after('serial_number');
            }
            if (!Schema::hasColumn('wheelchairs', 'theta')) {
                $table->double('theta')->nullable()->after('y_coordinate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wheelchairs', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'theta']);
            $table->double('battery')->nullable();
            $table->double('voltage')->nullable();
            $table->double('current')->nullable();
            $table->double('temperature')->nullable();
        });
    }
};
