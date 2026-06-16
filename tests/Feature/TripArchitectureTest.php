<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wheelchair;
use App\Models\Trip;
use App\Models\Floor;
use App\Models\Place;
use App\Enums\UserRoleEnum;

class TripArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Assume default roles exist or setup necessary mock data
    }

    public function test_companion_can_view_current_location_but_cannot_start_trip()
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'username' => 'owner_user',
            'password' => bcrypt('password'),
            'role' => UserRoleEnum::USER->value,
            'email_verified_at' => now(),
        ]);
        $companion = User::create([
            'name' => 'Companion',
            'email' => 'comp@test.com',
            'username' => 'comp_user',
            'password' => bcrypt('password'),
            'role' => UserRoleEnum::COMPANION->value,
            'email_verified_at' => now(),
        ]);
        
        // Connect companion to owner
        $companion->friends()->attach($owner->id, ['status' => 'accepted']);

        $org = \App\Models\Organization::create(['name' => 'Org', 'email' => 'org@test.com']);
        $building = \App\Models\Building::create(['name' => 'Build', 'organization_id' => $org->id]);
        $floor = Floor::create(['name' => 'Floor', 'building_id' => $building->id, 'number' => 1]);
        
        $wheelchair = Wheelchair::create([
            'user_id' => $owner->id,
            'api_key' => 'wh_key1',
            'serial_number' => 'sn_1',
            'connection_state' => 'online',
            'current_floor_id' => $floor->id,
            'x_coordinate' => 10.5,
            'y_coordinate' => 20.5,
            'theta' => 0,
        ]);

        $this->actingAs($companion);

        // Can view current location
        $response = $this->getJson('/api/wheelchairs/current');
        $response->assertStatus(200)
                 ->assertJsonPath('data.0.id', $wheelchair->id);

        // Cannot start trip
        $tripResponse = $this->postJson("/api/wheelchairs/{$wheelchair->id}/trips", [
            'mode' => 'autonomous'
        ]);
        $tripResponse->assertStatus(403);
    }

    public function test_owner_can_start_trip_and_coordinates_are_calculated()
    {
        $owner = User::create([
            'name' => 'Owner2',
            'email' => 'owner2@test.com',
            'username' => 'owner2_user',
            'password' => bcrypt('password'),
            'role' => UserRoleEnum::USER->value,
            'email_verified_at' => now(),
        ]);
        $org = \App\Models\Organization::create(['name' => 'Org', 'email' => 'org2@test.com']);
        $building = \App\Models\Building::create(['name' => 'Build', 'organization_id' => $org->id]);
        $floor = Floor::create(['name' => 'Floor', 'building_id' => $building->id, 'number' => 1]);
        $place = Place::create(['name' => 'Place1', 'floor_id' => $floor->id, 'x' => 100.0, 'y' => 200.0]);
        
        $wheelchair = Wheelchair::create([
            'user_id' => $owner->id,
            'api_key' => 'wh_key2',
            'serial_number' => 'sn_2',
            'connection_state' => 'online',
            'current_floor_id' => $floor->id,
            'x_coordinate' => 10.5,
            'y_coordinate' => 20.5,
        ]);

        $this->actingAs($owner);

        $response = $this->postJson("/api/wheelchairs/{$wheelchair->id}/trips", [
            'mode' => 'autonomous',
            'place_id' => $place->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('trips', [
            'wheelchair_id' => $wheelchair->id,
            'status' => 'started',
            'start_x' => 10.5,
            'start_y' => 20.5,
            'end_x' => 100.0,
            'end_y' => 200.0,
        ]);
    }

    public function test_cannot_start_trip_if_one_is_active()
    {
        $owner = User::create([
            'name' => 'Owner5',
            'email' => 'owner5@test.com',
            'username' => 'owner5_user',
            'password' => bcrypt('password'),
            'role' => UserRoleEnum::USER->value,
            'email_verified_at' => now(),
        ]);
        
        $wheelchair = Wheelchair::create([
            'user_id' => $owner->id,
            'api_key' => 'wh_key5',
            'serial_number' => 'sn_5',
            'connection_state' => 'online',
            'x_coordinate' => 10.5,
            'y_coordinate' => 20.5,
        ]);

        Trip::create([
            'wheelchair_id' => $wheelchair->id,
            'status' => 'started',
            'mode' => 'autonomous',
            'start_x' => 10.5,
            'start_y' => 20.5,
            'end_x' => 100.0,
            'end_y' => 200.0,
        ]);

        $this->actingAs($owner);

        $response = $this->postJson("/api/wheelchairs/{$wheelchair->id}/trips", [
            'mode' => 'autonomous',
            'end_x' => 50.0,
            'end_y' => 60.0,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('message', 'There is already an active trip. Please end or fail it before starting a new one.');
    }

    public function test_sos_trigger_fails_active_trip()
    {
        $owner = User::create([
            'name' => 'Owner3',
            'email' => 'owner3@test.com',
            'username' => 'owner3_user',
            'password' => bcrypt('password'),
            'role' => UserRoleEnum::USER->value,
            'email_verified_at' => now(),
        ]);
        $companion = User::create([
            'name' => 'Companion3',
            'email' => 'comp3@test.com',
            'username' => 'comp3_user',
            'password' => bcrypt('password'),
            'role' => UserRoleEnum::COMPANION->value,
            'email_verified_at' => now(),
        ]);
        \App\Models\ConnectionRequest::create([
            'sender_id' => $companion->id,
            'receiver_id' => $owner->id,
            'connection_type' => 'companion',
            'status' => 'accepted'
        ]);

        $wheelchair = Wheelchair::create([
            'user_id' => $owner->id,
            'api_key' => 'wh_key3',
            'serial_number' => 'sn_3',
            'connection_state' => 'online',
        ]);
        $trip = Trip::create([
            'wheelchair_id' => $wheelchair->id,
            'status' => 'started',
            'mode' => 'autonomous',
        ]);

        $this->actingAs($owner);

        $response = $this->postJson("/api/sos", [
            'latitude' => 12.34,
            'longitude' => 56.78,
            'message' => 'Help!'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'failed'
        ]);
    }

    public function test_iot_can_fail_trip()
    {
        $owner = User::create([
            'name' => 'Owner4',
            'email' => 'owner4@test.com',
            'username' => 'owner4_user',
            'password' => bcrypt('password'),
            'role' => UserRoleEnum::USER->value,
            'email_verified_at' => now(),
        ]);
        $wheelchair = Wheelchair::create([
            'user_id' => $owner->id,
            'api_key' => 'wh_testkey123',
            'serial_number' => 'sn_4',
            'connection_state' => 'online',
        ]);
        $trip = Trip::create([
            'wheelchair_id' => $wheelchair->id,
            'status' => 'started',
            'mode' => 'autonomous',
        ]);

        $response = $this->withHeaders([
            'api-key' => 'wh_testkey123',
        ])->postJson("/api/iot/trip/fail");

        $response->assertStatus(200);

        $this->assertDatabaseHas('trips', [
            'id' => $trip->id,
            'status' => 'failed'
        ]);
    }
}
