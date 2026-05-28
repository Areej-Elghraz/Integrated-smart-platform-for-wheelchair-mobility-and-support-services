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
        Schema::create('safety_monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wheelchair_id')->constrained('wheelchairs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->double('head_pitch')->nullable();
            $table->double('head_roll')->nullable();
            $table->double('chair_tilt_angle')->nullable();
            $table->double('acceleration_force')->nullable();
            $table->boolean('fall_detected')->default(false);
            $table->boolean('abnormal_posture')->default(false);
            $table->enum('safety_status', ['normal', 'warning', 'critical', 'emergency'])->default('normal');
            $table->timestamps();
        });

        Schema::create('current_safety_states', function (Blueprint $table) {
            $table->foreignId('wheelchair_id')->primary()->constrained('wheelchairs')->cascadeOnDelete();
            $table->boolean('fall_detected')->default(false);
            $table->boolean('abnormal_posture')->default(false);
            $table->boolean('sudden_motion_detected')->default(false);
            $table->enum('status', ['normal', 'warning', 'critical'])->default('normal');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('current_safety_states');
        Schema::dropIfExists('safety_monitoring_logs');
    }
};
