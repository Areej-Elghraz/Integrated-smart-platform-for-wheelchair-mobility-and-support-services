<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
if (!$user) {
    echo 'No users found.';
    exit;
}

$request = new \Illuminate\Http\Request();
$request->setUserResolver(function () use ($user) {
    return $user;
});

$wheelchair = $user->wheelchairs()->first();
        
$friends = $user->friends;
$doctor = $friends->where('role', 'doctor')->first();
$companions = $friends->where('role', 'companion')->map(fn($c) => ['name' => $c->name, 'phone' => $c->phone])->values();

$medicalConditions = $user->medicalConditions->pluck('name')->implode(' / ');

$currentTrip = null;
$vitalState = null;
$latestAlerts = [];

if ($wheelchair) {
    $trip = $wheelchair->trips()->where('status', 'in_progress')->first();
    if ($trip) {
        $currentTrip = [
            'is_active' => true,
            'start_location' => 'Unknown',
            'destination' => $trip->destination_place_id ? 'Place ID: ' . $trip->destination_place_id : 'Unknown',
            'current_coordinates' => ['x' => $wheelchair->x_coordinate, 'y' => $wheelchair->y_coordinate],
        ];
    }
    $vitalState = $wheelchair->aiRecommendations()->latest()->first();
    
    $alertTypes = ['health', 'obstacle', 'sos', 'battery'];
    foreach ($alertTypes as $type) {
        $event = $wheelchair->events()->where('type', $type)->latest()->first();
        $latestAlerts[$type] = $event ? [
            'message' => $event->message,
            'severity' => $event->severity,
            'timestamp' => $event->created_at->toIso8601String(),
        ] : null;
    }
}

$contextPayload = [
    'user_profile' => [
        'name' => $user->name,
        'medical_condition' => $medicalConditions ?: 'Unknown',
        'age' => $user->age,
        'weight' => $user->weight,
        'gender' => $user->gender,
    ],
    'relations' => [
        'doctor' => $doctor ? ['name' => $doctor->name, 'phone' => $doctor->phone] : null,
        'companions' => $companions,
    ],
    'wheelchair_status' => $wheelchair ? [
        'serial_number' => $wheelchair->serial_number,
        'battery' => $wheelchair->battery,
        'connection' => $wheelchair->connection_state,
    ] : null,
    'current_health_state' => $vitalState ? [
        'heart_rate' => $vitalState->heart_rate,
        'temperature' => $vitalState->temperature,
        'mpu_monitoring' => [
            'angle' => $vitalState->mpu_angle,
            'fall_detected' => $vitalState->fall_status === 'critical',
        ]
    ] : null,
    'current_trip' => $currentTrip,
    'latest_alerts' => $latestAlerts,
];

echo json_encode($contextPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

