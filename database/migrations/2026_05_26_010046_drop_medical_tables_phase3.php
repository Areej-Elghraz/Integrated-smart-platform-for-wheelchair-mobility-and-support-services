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
        Schema::dropIfExists('user_disability_types');
        Schema::dropIfExists('user_medical_conditions');
        Schema::dropIfExists('disability_types');
        Schema::dropIfExists('medical_conditions');
    }

    public function down(): void
    {
        // Not recreating them for phase 3, just leave empty or throw exception
    }
};
