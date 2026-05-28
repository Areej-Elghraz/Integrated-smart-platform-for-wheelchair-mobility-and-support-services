<?php

namespace App\Http\Controllers;

use App\Models\SensorReading;
use App\Models\Wheelchair;
use App\Http\Requests\Wheelchair\StoreSensorReadingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     */
    public function store(StoreSensorReadingRequest $request, $wheelchairId): JsonResponse
    {
        $wheelchair = Wheelchair::findOrFail($wheelchairId);
        $this->authorize('update', $wheelchair);

        $validated = $request->validated();

        $reading = SensorReading::create([
            'wheelchair_id'   => $wheelchair->id,
            'trip_id'         => $validated['trip_id'] ?? null,
            'heart_rate_min'  => $validated['heart_rate_min'] ?? null,
            'heart_rate_max'  => $validated['heart_rate_max'] ?? null,
            'heart_rate_avg'  => $validated['heart_rate_avg'] ?? null,
            'temperature_min' => $validated['temperature_min'] ?? null,
            'temperature_max' => $validated['temperature_max'] ?? null,
            'temperature_avg' => $validated['temperature_avg'] ?? null,
            'mpu_angle_min'   => $validated['mpu_angle_min'] ?? null,
            'mpu_angle_max'   => $validated['mpu_angle_max'] ?? null,
            'mpu_angle_avg'   => $validated['mpu_angle_avg'] ?? null,
            'reading_time'    => $validated['reading_time'],
        ]);

        // Trigger Dashboard Broadcast
        $targetUser = $wheelchair->user;
        $dashboardData = \App\Http\Controllers\DashboardController::getDashboardData($targetUser);
        broadcast(new \App\Events\DashboardUpdated($targetUser->id, 'user_dashboard', $dashboardData));

        return $this->successResponse('Sensor reading stored successfully.', parameters: ['data' => $reading]);
    }
}