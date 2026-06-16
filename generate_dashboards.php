<?php

use App\Models\User;
use App\Models\Wheelchair;
use App\Models\SensorReading;
use App\Models\SensorReadingAggregated;
use Illuminate\Support\Facades\DB;

// Find a user who has a wheelchair
$user = User::whereHas('wheelchairs')->first();
if (!$user) {
    echo "No user with wheelchair found.\n";
    exit;
}

$wheelchair = $user->wheelchairs()->first();

// Insert dummy aggregated data for today
$today = now()->startOfDay();
for ($i = 0; $i < 12; $i++) {
    $time = $today->copy()->addHours($i);
    SensorReadingAggregated::create([
        'wheelchair_id' => $wheelchair->id,
        'trip_id' => null,
        'heart_rate_min' => rand(60, 70),
        'heart_rate_max' => rand(80, 100),
        'heart_rate_avg' => rand(70, 85),
        'temperature_min' => rand(36, 36.5),
        'temperature_max' => rand(37, 37.5),
        'temperature_avg' => rand(36.5, 37),
        'mpu_angle_min' => rand(-5, 0),
        'mpu_angle_max' => rand(0, 10),
        'mpu_angle_avg' => rand(0, 5),
        'window_start' => $time,
        'window_end' => $time->copy()->addMinutes(10),
    ]);
}

// Ensure the user has some AI recommendations (Current Vitals)
\App\Models\AiRecommendation::updateOrCreate(
    ['wheelchair_id' => $wheelchair->id],
    [
        'heart_rate' => 75,
        'heart_rate_status' => 'low',
        'temperature' => 36.6,
        'temperature_status' => 'low',
        'mpu_angle' => 2,
        'fall_status' => 'low',
        'risk_level' => 'low',
        'recommendation' => 'All vitals are normal.',
    ]
);

// Add a dummy event
\App\Models\Event::create([
    'wheelchair_id' => $wheelchair->id,
    'type' => 'health',
    'severity' => 'low',
    'message' => 'Heart rate stable',
    'data' => ['heart_rate' => 75],
    'event_source' => 'system'
]);

// Get the dashboard data
$userDashboard = \App\Http\Controllers\DashboardController::getDashboardData($user, $user, 'today');

// Get Companion Dashboard
$companion = User::where('role', 'companion')->first();

$companionDashboard = [];
if ($companion) {
    $patients = $companion->friends()->with(['wheelchairs', 'medicalConditions'])->get();
    if ($patients->isEmpty()) { $patients = collect([$user]); }
    $companionDashboard = ['patients' => $patients];
}

// Get Doctor Dashboard
$doctor = User::where('role', 'doctor')->first();
$doctorDashboard = [];
if ($doctor) {
    $patients = $doctor->friends()->with(['wheelchairs', 'medicalConditions'])->get();
    if ($patients->isEmpty()) { $patients = collect([$user]); }
    $doctorDashboard = ['patients' => $patients];
}

// Get Org Admin Dashboard
$orgAdmin = User::where('role', 'organization_admin')->first();
$orgAdminDashboard = [];
if ($orgAdmin && $orgAdmin->organization) {
    $orgAdminDashboard = \App\Http\Controllers\DashboardController::getOrganizationDashboardData($orgAdmin);
}

// Write to JSON
file_put_contents('dashboard_responses.json', json_encode([
    'user_dashboard' => $userDashboard,
    'companion_dashboard' => $companionDashboard,
    'doctor_dashboard' => $doctorDashboard,
    'organization_dashboard' => $orgAdminDashboard,
], JSON_PRETTY_PRINT));

echo "Data generated in dashboard_responses.json\n";
