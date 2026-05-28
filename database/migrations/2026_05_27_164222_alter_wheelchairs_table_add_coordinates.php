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
            $table->double('x_coordinate')->nullable();
            $table->double('y_coordinate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wheelchairs', function (Blueprint $table) {
            $table->dropColumn(['x_coordinate', 'y_coordinate']);
        });
    }
};
