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
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wheelchair_id')->constrained()->onDelete('cascade');
            $table->double('heart_rate')->nullable();
            $table->enum('heart_rate_status', ['normal', 'medium', 'critical'])->default('normal');
            $table->double('temperature')->nullable();
            $table->enum('temperature_status', ['normal', 'medium', 'critical'])->default('normal');
            $table->double('mpu_angle')->nullable();
            $table->enum('fall_status', ['normal', 'medium', 'critical'])->default('normal');
            $table->string('type')->nullable(); // event type
            $table->enum('risk_level', ['normal', 'medium', 'critical'])->default('normal');
            $table->text('reason')->nullable();
            $table->text('recommendation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
    }
};
