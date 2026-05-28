<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SensorReadingsAggregatedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add dummy data for first wheelchair
        $wheelchair = \App\Models\Wheelchair::first();
        if (!$wheelchair) return;

        $now = now();

        $data = [];
        for ($i = 0; $i < 24; $i++) { // 24 hours of data
            $time = $now->copy()->subHours($i);
            $data[] = [
                'wheelchair_id' => $wheelchair->id,
                'trip_id' => null,
                'heart_rate_avg' => rand(65, 85),
                'temperature_avg' => 36 + (rand(0, 10) / 10),
                'mpu_angle_avg' => rand(0, 5),
                'window_start' => $time->copy()->startOfHour(),
                'window_end' => $time->copy()->endOfHour(),
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }

        \Illuminate\Support\Facades\DB::table('sensor_readings_aggregated')->insert($data);
    }
}
