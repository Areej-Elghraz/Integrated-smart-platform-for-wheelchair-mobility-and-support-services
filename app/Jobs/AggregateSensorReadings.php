<?php

namespace App\Jobs;

use App\Models\SensorReading;
use App\Models\SensorReadingAggregated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class AggregateSensorReadings implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // We aggregate the last 10 minutes of data
        $endTime = now();
        $startTime = $endTime->copy()->subMinutes(10);

        // Group by wheelchair_id and trip_id
        $aggregations = SensorReading::whereBetween('reading_time', [$startTime, $endTime])
            ->select(
                'wheelchair_id',
                'trip_id',
                DB::raw('MIN(heart_rate_min) as agg_hr_min'),
                DB::raw('MAX(heart_rate_max) as agg_hr_max'),
                DB::raw('AVG(heart_rate_avg) as agg_hr_avg'),
                DB::raw('MIN(temperature_min) as agg_temp_min'),
                DB::raw('MAX(temperature_max) as agg_temp_max'),
                DB::raw('AVG(temperature_avg) as agg_temp_avg'),
                DB::raw('MIN(mpu_angle_min) as agg_mpu_min'),
                DB::raw('MAX(mpu_angle_max) as agg_mpu_max'),
                DB::raw('AVG(mpu_angle_avg) as agg_mpu_avg')
            )
            ->groupBy('wheelchair_id', 'trip_id')
            ->get();

        foreach ($aggregations as $agg) {
            SensorReadingAggregated::create([
                'wheelchair_id' => $agg->wheelchair_id,
                'trip_id' => $agg->trip_id,
                'heart_rate_min' => $agg->agg_hr_min,
                'heart_rate_max' => $agg->agg_hr_max,
                'heart_rate_avg' => $agg->agg_hr_avg,
                'temperature_min' => $agg->agg_temp_min,
                'temperature_max' => $agg->agg_temp_max,
                'temperature_avg' => $agg->agg_temp_avg,
                'mpu_angle_min' => $agg->agg_mpu_min,
                'mpu_angle_max' => $agg->agg_mpu_max,
                'mpu_angle_avg' => $agg->agg_mpu_avg,
                'window_start' => $startTime,
                'window_end' => $endTime,
            ]);
        }

        // Delete raw sensor readings older than 1 hour
        SensorReading::where('reading_time', '<', now()->subHour())->delete();
    }
}
