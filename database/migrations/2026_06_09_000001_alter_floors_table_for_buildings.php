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
        Schema::table('floors', function (Blueprint $table) {
            // Drop old foreign key constraint and column
            $table->dropForeign(['place_id']);
            $table->dropColumn('place_id');
            
            // Add new building_id reference
            $table->foreignId('building_id')->nullable()->constrained('buildings')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('floors', function (Blueprint $table) {
            $table->dropForeign(['building_id']);
            $table->dropColumn('building_id');
            $table->foreignId('place_id')->nullable()->constrained('places')->cascadeOnDelete();
        });
    }
};
