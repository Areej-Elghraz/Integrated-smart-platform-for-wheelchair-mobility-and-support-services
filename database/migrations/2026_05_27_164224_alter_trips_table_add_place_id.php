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
        // Add the place_id foreign key only if the column does not already exist
        Schema::table('trips', function (Blueprint $table) {
            if (!Schema::hasColumn('trips', 'place_id')) {
                $table->foreignId('place_id')->nullable()->constrained()->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the column and foreign key only if it exists
        Schema::table('trips', function (Blueprint $table) {
            if (Schema::hasColumn('trips', 'place_id')) {
                $table->dropForeign(['place_id']);
                $table->dropColumn('place_id');
            }
        });
    }
};
