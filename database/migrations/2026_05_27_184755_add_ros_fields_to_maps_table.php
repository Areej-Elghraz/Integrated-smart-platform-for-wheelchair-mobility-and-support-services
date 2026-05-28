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
        Schema::table('maps', function (Blueprint $table) {
            $table->string('mode')->nullable();
            $table->integer('negate')->nullable();
            $table->double('occupied_thresh')->nullable();
            $table->double('free_thresh')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->dropColumn(['mode', 'negate', 'occupied_thresh', 'free_thresh']);
        });
    }
};
