<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Wheelchair;
use App\Models\MedicalCondition;
use App\Models\Trip;
use App\Models\AiRecommendation;
use App\Models\Event;

// 1. Create a User
$user = User::create([
    'name' => 'Ahmed The Patient',
    'email' => 'ahmed'.uniqid().'@test.com',
    'password' => bcrypt('password'),
    'birth_date' => '1996-01-01',
    'weight' => 70,
    'gender' => 'male',
    'role' => 'user'
]);

// 2. Add Medical Condition
$condition = MedicalCondition::firstOrCreate(['name_en' => 'Lower Body Paralysis'], ['name_ar' => '??? ????']);
$user->medicalConditions()->attach($condition->id);

// 3. Add Doctor & Companion (Friends)
$doctor = User::create(['name' => 'Dr. Smith', 'email' => 'doc'.uniqid().'@test.com', 'password' => bcrypt('123'), 'role' => 'doctor', 'phone' => '+20'.mt_rand(100000000, 999999999)]);
$companion = User::create(['name' => 'Mona', 'email' => 'mona'.uniqid().'@test.com', 'password' => bcrypt('123'), 'role' => 'companion', 'phone' => '+20'.mt_rand(100000000, 999999999)]);

// Attach friends
$user->friendsOfMine()->attach($doctor->id, ['status' => 'accepted']);
$user->friendsOfMine()->attach($companion->id, ['status' => 'accepted']);

// 4. Create Wheelchair
$wheelchair = Wheelchair::create([
    'user_id' => $user->id,
    'serial_number' => 'CHAIR-TEST-001',
    'battery' => 85,
    'connection_state' => 'online',
    'x_coordinate' => 10.5,
    'y_coordinate' => 20.2,
]);

// 5. Create active trip
$trip = Trip::create([
    'wheelchair_id' => $wheelchair->id,
    'user_id' => $user->id,
    'status' => 'in_progress',
    'destination_place_id' => 5,
]);

// 6. Create Health State (AiRecommendation)
AiRecommendation::create([
    'wheelchair_id' => $wheelchair->id,
    'heart_rate' => 110,
    'temperature' => 37.5,
    'mpu_angle' => 45,
    'fall_status' => 'critical',
]);

// 7. Create Events (Alerts)
Event::create([
    'wheelchair_id' => $wheelchair->id,
    'trip_id' => $trip->id,
    'type' => 'health',
    'severity' => 'critical',
    'message' => 'High Heart Rate Detected',
]);
Event::create([
    'wheelchair_id' => $wheelchair->id,
    'trip_id' => $trip->id,
    'type' => 'obstacle',
    'severity' => 'medium',
    'message' => 'Stairs Ahead',
]);
Event::create([
    'wheelchair_id' => $wheelchair->id,
    'trip_id' => $trip->id,
    'type' => 'battery',
    'severity' => 'medium',
    'message' => 'Battery Low',
]);

// Run the same payload extraction logic:
$friends = $user->friends;
$doctorFriend = $friends->where('role', 'doctor')->first();
$companionsList = $friends->where('role', 'companion')->map(fn($c) => ['name' => $c->name, 'phone' => $c->phone])->values();

$medicalConditions = $user->medicalConditions->pluck('name')->implode(' / ');

$currentTrip = null;
$vitalState = null;
$latestAlerts = [];

if ($wheelchair) {
    $tripCheck = $wheelchair->trips()->where('status', 'in_progress')->first();
    if ($tripCheck) {
        $currentTrip = [
            'is_active' => true,
            'start_location' => 'Unknown',
            'destination' => $tripCheck->destination_place_id ? 'Place ID: ' . $tripCheck->destination_place_id : 'Unknown',
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
        'doctor' => $doctorFriend ? ['name' => $doctorFriend->name, 'phone' => $doctorFriend->phone] : null,
        'companions' => $companionsList,
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

