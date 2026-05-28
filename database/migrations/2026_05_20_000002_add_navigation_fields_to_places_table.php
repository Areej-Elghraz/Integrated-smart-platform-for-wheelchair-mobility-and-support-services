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
        Schema::table('places', function (Blueprint $table) {
            $table->foreignId('floor_id')->nullable()->after('parent_place_id')->constrained('floors')->nullOnDelete();
            $table->double('x')->nullable()->after('floor_id');
            $table->double('y')->nullable()->after('x');
            $table->double('z')->nullable()->after('y');
            $table->double('rotation')->nullable()->after('z');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropForeign(['floor_id']);
            $table->dropColumn(['floor_id', 'x', 'y', 'z', 'rotation']);
        });
    }
};
