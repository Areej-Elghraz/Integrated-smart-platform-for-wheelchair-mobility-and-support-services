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
        Schema::create('wheelchairs', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('model_name');
            $table->string('firmware_version');
            $table->enum('current_status', ['moving', 'stopped', 'offline', 'charging', 'error'])->default('offline');
            $table->integer('battery_percentage')->default(100);
            $table->double('current_speed')->default(0.0);
            $table->enum('mode', ['manual_joystick', 'manual_app', 'autonomous', 'emergency', 'stairs_mode', 'idle', 'charging'])->default('idle');
            $table->foreignId('current_floor_id')->nullable()->constrained('floors')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('connected_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_connected')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->enum('connection_type', ['wifi', 'bluetooth'])->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wheelchair_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wheelchair_id')->constrained('wheelchairs')->cascadeOnDelete();
            $table->string('sensor_type'); // e.g. lidar, ultrasonic, camera, mpu, temperature, heart_rate
            $table->string('sensor_name');
            $table->string('firmware_version');
            $table->enum('status', ['active', 'inactive', 'error'])->default('inactive');
            $table->timestamp('last_reading_time')->nullable();
            $table->timestamps();
        });

        Schema::create('current_vital_states', function (Blueprint $table) {
            $table->foreignId('wheelchair_id')->primary()->constrained('wheelchairs')->cascadeOnDelete();
            $table->double('current_temperature');
            $table->integer('current_heart_rate');
            $table->enum('status', ['normal', 'warning', 'critical', 'emergency', 'offline'])->default('normal');
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('current_vital_states');
        Schema::dropIfExists('wheelchair_sensors');
        Schema::dropIfExists('wheelchairs');
    }
};
