<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use App\Models\SensorReading;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // 3. Sync Vital States
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
                // Ensure datetime formatting
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

                $parsed['created_at'] = now();
                $parsed['updated_at'] = now();
                $records[] = $parsed;
            }
        }

        if (!empty($records)) {
            // Upsert records based on wheelchair_id
            DB::table('wheelchair_movement_states')->upsert($records, ['wheelchair_id'], [
                'trip_id',
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
            // Upsert records based on wheelchair_id
            DB::table('current_vital_states')->upsert($records, ['wheelchair_id'], [
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