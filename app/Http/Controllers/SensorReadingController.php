<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use App\Models\Wheelchair;
use App\Http\Requests\Wheelchair\StoreSensorReadingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class SensorReadingController extends ApiController
{
    /**
     * Get sensor readings for a wheelchair.
     */
    public function index($wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);
        $this->authorize('view', $wheelchair);

        $readings = SensorReading::where('wheelchair_id', $wheelchair->id)
            ->latest('reading_time')
            ->paginate(50);

        return $this->successResponse('Sensor readings retrieved.', parameters: ['data' => $readings]);
    }

    /**
     * Store new sensor readings from the hardware.
     * BEST PRACTICE: High-frequency IoT data is buffered in Redis to protect MySQL.
     */
    public function store(StoreSensorReadingRequest $request): JsonResponse
    {
        $wheelchair = $request->get('authenticated_wheelchair');
        if (!$wheelchair) {
            return $this->errorResponse('Unauthorized wheelchair.', 403);
        }

        $validated = $request->validated();

        $activeTrip = \App\Models\Trip::where('wheelchair_id', $wheelchair->id)
            ->where('status', 'started')
            ->first();

        $readingTime = isset($validated['reading_time'])
            ? Carbon::parse($validated['reading_time'])->format('Y-m-d H:i:s')
            : now()->format('Y-m-d H:i:s');

        $readingData = [
            'wheelchair_id'   => $wheelchair->id,
            'trip_id'         => $activeTrip ? $activeTrip->id : null,
            'heart_rate_min'  => $validated['heart_rate_min'] ?? null,
            'heart_rate_max'  => $validated['heart_rate_max'] ?? null,
            'heart_rate_avg'  => $validated['heart_rate_avg'] ?? null,
            'temperature_min' => $validated['temperature_min'] ?? null,
            'temperature_max' => $validated['temperature_max'] ?? null,
            'temperature_avg' => $validated['temperature_avg'] ?? null,
            'mpu_angle_min'   => $validated['mpu_angle_min'] ?? null,
            'mpu_angle_max'   => $validated['mpu_angle_max'] ?? null,
            'mpu_angle_avg'   => $validated['mpu_angle_avg'] ?? null,
            'reading_time'    => $readingTime,
        ];

        $warning = null;
        try {
            // 1. Buffer in Redis for the 5-minute bulk sync to MySQL
            Redis::rpush('buffer:sensor_readings', json_encode($readingData));
            
            // 2. Save the absolute latest reading to a dedicated Redis key for real-time dashboard display
            Redis::set("latest_sensor_reading:{$wheelchair->id}", json_encode($readingData));
            
        } catch (\Exception $e) {
            $warning = 'Redis unavailable. Falling back to direct MySQL insert.';
            SensorReading::create($readingData);
        }

        return $this->successResponse('Sensor reading processed successfully.', parameters: [
            'data'    => $readingData,
            'warning' => $warning,
        ]);
    }
}