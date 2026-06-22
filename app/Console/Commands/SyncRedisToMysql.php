<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\SensorReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class SyncRedisToMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:sync-to-mysql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync buffered data from Redis to MySQL (Runs every 5 minutes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Redis to MySQL sync...');
        Log::info('Syncing Redis buffers to MySQL.');

        // 1. Sync Sensor Readings
        $this->syncSensorReadings();

        // 2. Sync Movement States
        $this->syncMovementStates();

        // 3. Sync Vital States (ai_recommendations table)
        $this->syncVitalStates();

        $this->info('Sync completed successfully.');
    }

    private function syncSensorReadings()
    {
        $key = 'buffer:sensor_readings';
        $records = [];

        // Pop all available records from the Redis list
        while ($data = Redis::lpop($key)) {
            $parsed = json_decode($data, true);
            if ($parsed) {
                // Convert ISO 8601 datetime to MySQL format
                if (isset($parsed['reading_time'])) {
                    try {
                        $parsed['reading_time'] = Carbon::parse($parsed['reading_time'])->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        // If parsing fails, use current time
                        $parsed['reading_time'] = now()->format('Y-m-d H:i:s');
                    }
                }

                $parsed['created_at'] = now();
                $parsed['updated_at'] = now();
                $records[] = $parsed;
            }
        }

        if (!empty($records)) {
            // Bulk insert
            SensorReading::insert($records);
            $this->info(count($records) . ' sensor readings inserted.');
        }
    }

    private function syncMovementStates()
    {
        $key = 'buffer:movement_states';
        $records = [];

        // Pop all available records from the Redis list
        while ($data = Redis::lpop($key)) {
            $parsed = json_decode($data, true);
            if ($parsed) {
                if (isset($parsed['position']) && is_array($parsed['position'])) {
                    $parsed['position'] = json_encode($parsed['position']);
                }

                // Remove trip_id if present - this column doesn't exist in wheelchair_movement_states table
                unset($parsed['trip_id']);

                $parsed['created_at'] = now();
                $parsed['updated_at'] = now();
                $records[] = $parsed;
            }
        }

        if (!empty($records)) {
            // Upsert records based on wheelchair_id (without trip_id)
            DB::table('wheelchair_movement_states')->upsert($records, ['wheelchair_id'], [
                'movement_status',
                'speed',
                'position',
                'theta',
                'mode',
                'risk_level',
                'obstacle_detected',
                'obstacle_distance',
                'updated_at'
            ]);
            $this->info(count($records) . ' movement states upserted.');
        }
    }

    private function syncVitalStates()
    {
        $key = 'buffer:vital_states';
        $records = [];

        // Pop all available records from the Redis list
        while ($data = Redis::lpop($key)) {
            $parsed = json_decode($data, true);
            if ($parsed) {
                $parsed['created_at'] = now();
                $parsed['updated_at'] = now();
                $records[] = $parsed;
            }
        }

        if (!empty($records)) {
            // Upsert records into ai_recommendations table (replaces old current_vital_states)
            DB::table('ai_recommendations')->upsert($records, ['wheelchair_id'], [
                'heart_rate',
                'heart_rate_status',
                'temperature',
                'temperature_status',
                'mpu_angle',
                'fall_status',
                'type',
                'risk_level',
                'reason',
                'recommendation',
                'updated_at'
            ]);
            $this->info(count($records) . ' vital states upserted.');
        }
    }
}