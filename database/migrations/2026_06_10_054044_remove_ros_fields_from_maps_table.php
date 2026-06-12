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
            $table->dropColumn([
                'origin',
                'resolution',
                'mode',
                'negate',
                'occupied_thresh',
                'free_thresh',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maps', function (Blueprint $table) {
            $table->json('origin')->nullable();
            $table->double('resolution')->default(0.05);
            $table->string('mode')->nullable();
            $table->integer('negate')->nullable();
            $table->double('occupied_thresh')->nullable();
            $table->double('free_thresh')->nullable();
        });
    }
};
