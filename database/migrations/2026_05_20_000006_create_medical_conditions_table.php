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
        Schema::create('medical_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('category_en');
            $table->string('category_ar');
            $table->text('description_en');
            $table->text('description_ar');
            $table->timestamps();
        });

        Schema::create('user_medical_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('medical_condition_id')->constrained('medical_conditions')->cascadeOnDelete();
            $table->enum('severity', ['mild', 'moderate', 'severe']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_medical_conditions');
        Schema::dropIfExists('medical_conditions');
    }
};
