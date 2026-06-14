<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ChairpalDummyDataSeeder extends Seeder
{
    /**
     * Seed dummy data across ALL core tables for demonstration and testing.
     */
    public function run(): void
    {
        // ── 1. Users (Patient, Companion, Doctor, Organization) ──
        $patient = \App\Models\User::firstOrCreate(
            ['email' => 'patient@demo.com'],
            [
                'name' => 'Ahmed Patient',
                'username' => 'ahmed_patient',
                'password' => Hash::make('password'),
                'role' => 'user',
                'phone' => '01000000001',
                'gender' => 'male',
                'birth_date' => '1995-06-15',
                'weight' => 70,
                'height' => 175,
                'email_verified_at' => now(),
            ]
        );

        $companion = \App\Models\User::firstOrCreate(
            ['email' => 'companion@demo.com'],
            [
                'name' => 'Sara Companion',
                'username' => 'sara_companion',
                'password' => Hash::make('password'),
                'role' => 'companion',
                'phone' => '01000000002',
                'gender' => 'female',
                'birth_date' => '1993-03-20',
                'email_verified_at' => now(),
            ]
        );

        $doctor = \App\Models\User::firstOrCreate(
            ['email' => 'doctor@demo.com'],
            [
                'name' => 'Dr. Mohamed',
                'username' => 'dr_mohamed',
                'password' => Hash::make('password'),
                'role' => 'doctor',
                'phone' => '01000000003',
                'gender' => 'male',
                'birth_date' => '1980-01-10',
                'email_verified_at' => now(),
            ]
        );

        $orgUser = \App\Models\User::firstOrCreate(
            ['email' => 'org@demo.com'],
            [
                'name' => 'Hospital Admin',
                'username' => 'hospital_admin',
                'password' => Hash::make('password'),
                'role' => 'organization',
                'phone' => '01000000004',
                'email_verified_at' => now(),
            ]
        );

        // ── 2. Medical Conditions ──
        $conditions = [];
        foreach (['Diabetes' => 'سكري', 'Hypertension' => 'ضغط دم مرتفع', 'Asthma' => 'ربو'] as $en => $ar) {
            $conditions[] = \App\Models\MedicalCondition::firstOrCreate(
                ['name_en' => $en],
                ['name_ar' => $ar]
            );
        }
        // Attach conditions to patient
        $patient->medicalConditions()->syncWithoutDetaching(
            collect($conditions)->pluck('id')->toArray()
        );

        // ── 3. Friendships (Patient ↔ Companion, Patient ↔ Doctor) ──
        DB::table('user_friends')->updateOrInsert(
            ['user_id' => $patient->id, 'friend_id' => $companion->id],
            ['status' => 'accepted', 'accepted_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('user_friends')->updateOrInsert(
            ['user_id' => $patient->id, 'friend_id' => $doctor->id],
            ['status' => 'accepted', 'accepted_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        // ── 4. Organization ──
        $org = \App\Models\Organization::firstOrCreate(
            ['owner_id' => $orgUser->id],
            ['name' => 'Cairo Hospital', 'description' => 'A modern rehabilitation hospital.']
        );

        // ── 5. Country & City ──
        $country = \App\Models\Country::firstOrCreate(['name' => 'Egypt']);
        $city = \App\Models\City::firstOrCreate(
            ['name' => 'Cairo', 'country_id' => $country->id]
        );

        // ── 6. Place (with Points for center calculation) ──
        $place = \App\Models\Place::firstOrCreate(
            ['name' => 'Rehab Room A', 'organization_id' => $org->id],
            [
                'description' => 'Physical rehabilitation room on the ground floor.',
                'owner_id' => $orgUser->id,
                'country_id' => $country->id,
                'city_id' => $city->id,
                'points' => [
                    ['x' => 2.0, 'y' => 3.0],
                    ['x' => 4.0, 'y' => 5.0],
                    ['x' => 6.0, 'y' => 7.0],
                ],
                'x' => 4.0,  // center of points
                'y' => 5.0,  // center of points
            ]
        );

        $place2 = \App\Models\Place::firstOrCreate(
            ['name' => 'Garden Area', 'organization_id' => $org->id],
            [
                'description' => 'Outdoor garden area.',
                'owner_id' => $orgUser->id,
                'country_id' => $country->id,
                'city_id' => $city->id,
                'x' => 10.0,
                'y' => 15.0,
            ]
        );

        // ── 7. Wheelchair ──
        $wheelchair = \App\Models\Wheelchair::firstOrCreate(
            ['serial_number' => 'WC-DEMO-001'],
            [
                'user_id' => $patient->id,
                'battery' => 85,
                'voltage' => 24.1,
                'current' => 1.2,
                'temperature' => 28.5,
                'connection_state' => 'online',
                'x_coordinate' => 3.5,
                'y_coordinate' => 4.2,
            ]
        );

        // ── 8. AI Recommendations (Vitals) ──
        $statuses = ['low', 'medium', 'critical'];
        for ($i = 0; $i < 5; $i++) {
            \App\Models\AiRecommendation::create([
                'wheelchair_id' => $wheelchair->id,
                'heart_rate' => rand(60, 100),
                'heart_rate_status' => $statuses[array_rand($statuses)],
                'temperature' => 36 + (rand(0, 20) / 10),
                'temperature_status' => 'normal',
                'mpu_angle' => rand(0, 15),
                'fall_status' => $i === 4 ? 'critical' : 'normal',
                'type' => 'periodic',
                'risk_level' => $i === 4 ? 'critical' : 'normal',
                'reason' => $i === 4 ? 'Patient fall detected' : null,
                'recommendation' => $i === 4 ? 'Immediate medical attention required' : 'Patient vitals are stable',
                'created_at' => now()->subHours(5 - $i),
            ]);
        }

        // ── 9. Trips (1 completed, 1 active) ──
        $completedTrip = \App\Models\Trip::create([
            'wheelchair_id' => $wheelchair->id,
            'place_id' => $place->id,
            'mode' => 'autonomous',
            'status' => 'completed',
            'started_at' => now()->subHours(3),
            'ended_at' => now()->subHours(2),
        ]);

        $activeTrip = \App\Models\Trip::create([
            'wheelchair_id' => $wheelchair->id,
            'place_id' => $place2->id,
            'mode' => 'manual',
            'status' => 'started',
            'started_at' => now()->subMinutes(15),
        ]);

        // ── 10. Events (health, obstacle) ──
        $eventData = [
            ['type' => 'health', 'severity' => 'normal', 'message' => 'Heart rate stable at 72 bpm', 'event_source' => 'ai'],
            ['type' => 'battery', 'severity' => 'medium', 'message' => 'Battery dropped to 25%', 'event_source' => 'system'],
            ['type' => 'health', 'severity' => 'critical', 'message' => 'Fall detected near corridor B', 'event_source' => 'ai'],
            ['type' => 'obstacle', 'severity' => 'normal', 'message' => 'Navigating around small object', 'event_source' => 'system'],
        ];
        foreach ($eventData as $idx => $evt) {
            \App\Models\Event::create(array_merge($evt, [
                'wheelchair_id' => $wheelchair->id,
                'trip_id' => $idx < 2 ? $completedTrip->id : $activeTrip->id,
                'data' => ['source' => 'seeder'],
                'created_at' => now()->subMinutes(60 - ($idx * 10)),
            ]));
        }

        // ── 11. Sensor Readings Aggregated (24h of hourly data) ──
        $now = now();
        $aggregatedData = [];
        for ($i = 0; $i < 24; $i++) {
            $time = $now->copy()->subHours($i);
            $aggregatedData[] = [
                'wheelchair_id' => $wheelchair->id,
                'trip_id' => null,
                'heart_rate_min' => rand(55, 65),
                'heart_rate_max' => rand(85, 100),
                'heart_rate_avg' => rand(65, 85),
                'temperature_min' => 36.0,
                'temperature_max' => 37.0 + (rand(0, 5) / 10),
                'temperature_avg' => 36 + (rand(0, 10) / 10),
                'mpu_angle_min' => 0,
                'mpu_angle_max' => rand(5, 15),
                'mpu_angle_avg' => rand(0, 5),
                'window_start' => $time->copy()->startOfHour(),
                'window_end' => $time->copy()->endOfHour(),
                'created_at' => $time,
                'updated_at' => $time,
            ];
        }
        DB::table('sensor_readings_aggregated')->insert($aggregatedData);

        // ── 12. Floor & Map ──
        $floor = \App\Models\Floor::firstOrCreate(
            ['name' => 'Ground Floor', 'organization_id' => $org->id],
            ['number' => 0]
        );

        \App\Models\Map::firstOrCreate(
            ['floor_id' => $floor->id],
            [
                'map_file' => 'demo_ground_floor',
                'extension' => 'pgm',
                'yaml_file' => 'demo_ground_floor.yaml',
                'resolution' => 0.05,
                'origin' => json_encode([-10.0, -10.0, 0.0]),
                'width' => 400,
                'height' => 400,
                'mode' => 'trinary',
                'negate' => 0,
                'occupied_thresh' => 0.65,
                'free_thresh' => 0.196,
            ]
        );

        $this->command->info('✅ Chairpal dummy data seeded successfully!');
        $this->command->info("   Patient:    {$patient->email} / password");
        $this->command->info("   Companion:  {$companion->email} / password");
        $this->command->info("   Doctor:     {$doctor->email} / password");
        $this->command->info("   Org Admin:  {$orgUser->email} / password");
    }
}
