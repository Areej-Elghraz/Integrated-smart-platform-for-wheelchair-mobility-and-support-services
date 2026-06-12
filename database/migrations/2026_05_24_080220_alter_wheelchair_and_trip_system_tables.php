<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modify wheelchairs Table
        Schema::table('wheelchairs', function (Blueprint $table) {
            // Drop foreign keys if they exist (skip for sqlite testing)
            if (DB::getDriverName() !== 'sqlite') {
                $foreignKeys = Schema::getForeignKeys('wheelchairs');
                $fkNames = array_column($foreignKeys, 'name');

                if (in_array('wheelchairs_current_floor_id_foreign', $fkNames)) {
                    $table->dropForeign('wheelchairs_current_floor_id_foreign');
                }
                if (in_array('wheelchairs_assigned_user_id_foreign', $fkNames)) {
                    $table->dropForeign('wheelchairs_assigned_user_id_foreign');
                }
                if (in_array('wheelchairs_connected_user_id_foreign', $fkNames)) {
                    $table->dropForeign('wheelchairs_connected_user_id_foreign');
                }
            }

            // Drop columns if they exist
            $columnsToDrop = [];
            foreach (
                [
                    'model_name',
                    'firmware_version',
                    'current_status',
                    'battery_percentage',
                    'current_speed',
                    'mode',
                    'current_floor_id',
                    'assigned_user_id',
                    'connected_user_id',
                    'is_connected',
                    'connected_at',
                    'disconnected_at',
                    'connection_type',
                    'is_online',
                    'last_seen_at'
                ] as $col
            ) {
                if (Schema::hasColumn('wheelchairs', $col)) {
                    // Skip foreign key columns for SQLite to avoid constraint errors
                    if (DB::getDriverName() === 'sqlite' && in_array($col, ['current_floor_id', 'assigned_user_id', 'connected_user_id'])) {
                        continue;
                    }
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // Add new columns if they don't exist
            if (!Schema::hasColumn('wheelchairs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('wheelchairs', 'battery')) {
                $table->double('battery')->nullable();
                $table->double('voltage')->nullable();
                $table->double('current')->nullable();
                $table->double('temperature')->nullable();
                $table->enum('connection_state', ['online', 'offline'])->default('offline');
            }
        });

        // 2. Modify trips Table
        if (!Schema::hasTable('trips')) {
            Schema::create('trips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->unsignedBigInteger('e_chair_id')->nullable();
                $table->string('start_location')->nullable();
                $table->string('end_location')->nullable();
                $table->string('navigation_mode')->nullable();
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->double('total_distance')->nullable();
                $table->double('total_time')->nullable();
                $table->text('metadata')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('trips', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['e_chair_id']);
            }

            $columnsToDrop = [
                'user_id',
                'e_chair_id',
                'start_location',
                'end_location',
                'navigation_mode',
                'start_time',
                'end_time',
                'total_distance',
                'total_time',
                'metadata',
                'status'
            ];

            // Skip foreign key columns for SQLite to avoid constraint errors
            if (DB::getDriverName() === 'sqlite') {
                $columnsToDrop = array_diff($columnsToDrop, ['user_id', 'e_chair_id']);
            }

            $table->dropColumn($columnsToDrop);

            $table->foreignId('wheelchair_id')->constrained('wheelchairs')->cascadeOnDelete();
            $table->enum('mode', ['autonomous', 'manual']);
            $table->enum('status', ['started', 'completed']);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
        });

        // 3. Modify current_vital_states Table
        Schema::table('current_vital_states', function (Blueprint $table) {
            $table->dropColumn(['current_temperature', 'current_heart_rate', 'status', 'updated_at']);

            $table->double('heart_rate')->default(0);
            $table->enum('heart_rate_status', ['normal', 'medium', 'warning'])->default('normal');
            $table->double('temperature')->default(0);
            $table->enum('temperature_status', ['normal', 'medium', 'warning'])->default('normal');
            $table->double('mpu_angle')->default(0);
            $table->boolean('fall_status')->default(false);
            $table->string('type')->default('health');
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->string('reason')->nullable();
            $table->string('recommendation')->nullable();
            $table->timestamps();
        });

        // 4. Create trip_movement_states Table
        Schema::create('trip_movement_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->unique()->constrained('trips')->cascadeOnDelete();
            $table->enum('movement_status', ['moving', 'idle']);
            $table->double('speed');
            $table->json('position');
            $table->double('theta');
            $table->enum('mode', ['autonomous', 'manual']);
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->boolean('obstacle_detected');
            $table->double('obstacle_distance');
            $table->timestamps();
        });

        // 5. Create sensor_readings Table
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wheelchair_id')->constrained('wheelchairs')->cascadeOnDelete();
            $table->enum('sensor_type', ['mpu', 'heart', 'temperature']);
            $table->json('value');
            $table->timestamp('reading_time');
            $table->timestamps();
        });

        // 6. Create events Table
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wheelchair_id')->constrained('wheelchairs')->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->cascadeOnDelete();
            $table->enum('type', ['health', 'obstacle']);
            $table->enum('severity', ['low', 'medium', 'high']);
            $table->text('message');
            $table->json('data');
            $table->enum('event_source', ['ai', 'system'])->default('ai');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('sensor_readings');
        Schema::dropIfExists('trip_movement_states');

        // Down methods for table alterations skipped for brevity since this is an alter.
        // Reversing these drops would require redefining old columns.
    }
};
