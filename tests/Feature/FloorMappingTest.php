<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Map;
use App\Models\Organization;
use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FloorMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_global_buildings_and_floors()
    {
        $admin = User::factory()->create(['role' => UserRoleEnum::SUPER_ADMIN->value]);
        $org = Organization::factory()->create(['owner_id' => null]); // Global Org

        // Admin creates building
        $response = $this->actingAs($admin)->postJson("/api/organizations/{$org->id}/buildings", [
            'name' => 'Main Hospital',
            'description' => 'Main block',
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $response->assertStatus(201);
        $buildingId = $response->json('data.id');
        $this->assertDatabaseHas('buildings', ['id' => $buildingId, 'owner_id' => null]);

        // Admin creates floor
        $response = $this->actingAs($admin)->postJson("/api/buildings/{$buildingId}/floors", [
            'name' => 'First Floor',
            'number' => 1,
        ]);

        $response->assertStatus(201);
    }

    public function test_user_cannot_create_floor_in_admin_building()
    {
        $user = User::factory()->create(['role' => UserRoleEnum::USER->value]);
        $org = Organization::factory()->create(['owner_id' => null]);
        $building = Building::create([
            'name' => 'Admin Building',
            'organization_id' => $org->id,
            'owner_id' => null, // Admin building
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $response = $this->actingAs($user)->postJson("/api/buildings/{$building->id}/floors", [
            'name' => 'First Floor',
            'number' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_create_building_in_global_org()
    {
        $user = User::factory()->create(['role' => UserRoleEnum::USER->value]);
        $org = Organization::factory()->create(['owner_id' => null]);

        $response = $this->actingAs($user)->postJson("/api/organizations/{$org->id}/buildings", [
            'name' => 'My Private Ward',
            'description' => 'User private',
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $response->assertStatus(201);
        $buildingId = $response->json('data.id');
        $this->assertDatabaseHas('buildings', ['id' => $buildingId, 'owner_id' => $user->id]);

        // User can create floor in their own building
        $response = $this->actingAs($user)->postJson("/api/buildings/{$buildingId}/floors", [
            'name' => 'My Floor',
            'number' => 1,
        ]);

        $response->assertStatus(201);
    }

    public function test_map_uploading_by_owner()
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => UserRoleEnum::USER->value]);
        $org = Organization::factory()->create(['owner_id' => null]);
        $building = Building::create([
            'name' => 'User Building',
            'organization_id' => $org->id,
            'owner_id' => $user->id,
            'latitude' => 0,
            'longitude' => 0,
        ]);
        $floor = Floor::create([
            'building_id' => $building->id,
            'name' => 'Ground',
            'number' => 0,
        ]);

        $mapFile = UploadedFile::fake()->image('map.png');

        $response = $this->actingAs($user)->postJson("/api/floors/{$floor->id}/map", [
            'map_file' => $mapFile,
            'width' => 100,
            'height' => 100,
            'resolution' => 0.05,
            'origin' => [0, 0, 0],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('maps', ['floor_id' => $floor->id]);
    }

    public function test_iot_map_uploading_by_wheelchair()
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => UserRoleEnum::USER->value]);
        $org = Organization::factory()->create(['owner_id' => null]);
        $building = Building::create([
            'name' => 'User Building',
            'organization_id' => $org->id,
            'owner_id' => $user->id,
            'latitude' => 0,
            'longitude' => 0,
        ]);
        $floor = Floor::create([
            'building_id' => $building->id,
            'name' => 'Ground',
            'number' => 0,
        ]);

        $wheelchair = \App\Models\Wheelchair::create([
            'api_key' => 'TEST_WHEELCHAIR_KEY_123',
            'serial_number' => 'SN12345678',
            'user_id' => $user->id,
            'connection_state' => 'online',
        ]);

        $mapFile = UploadedFile::fake()->image('map.png');

        $response = $this->postJson("/api/iot/floors/{$floor->id}/map", [
            'map_file' => $mapFile,
            'width' => 100,
            'height' => 100,
            'resolution' => 0.05,
            'origin' => [0, 0, 0],
        ], [
            'api-key' => 'TEST_WHEELCHAIR_KEY_123'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('maps', ['floor_id' => $floor->id]);
    }

    public function test_mapping_permission_requires_floor_id_and_validates_it()
    {
        $user = User::factory()->create(['role' => UserRoleEnum::USER->value]);
        \Laravel\Sanctum\Sanctum::actingAs($user, [\App\Enums\TokenAbilityEnum::ACCESS_TOKEN->value]);

        $response = $this->getJson('/api/wheelchairs/mapping-permission');
        $response->assertStatus(422); // Validation error (floor_id required)
        $response->assertJsonValidationErrors(['floor_id']);

        $response = $this->getJson('/api/wheelchairs/mapping-permission?floor_id=99999');
        $response->assertStatus(422); // Validation error (exists in floors)
    }

    public function test_mapping_permission_checks_online_wheelchair_and_ownership()
    {
        $user = User::factory()->create(['role' => UserRoleEnum::USER->value]);
        $otherUser = User::factory()->create(['role' => UserRoleEnum::USER->value]);

        $org = Organization::factory()->create(['owner_id' => null]);
        $building = Building::create([
            'name' => 'User Building',
            'organization_id' => $org->id,
            'owner_id' => $user->id,
            'latitude' => 0,
            'longitude' => 0,
        ]);
        $floor = Floor::create([
            'building_id' => $building->id,
            'name' => 'Ground',
            'number' => 0,
        ]);

        // Case 1: Owner of the building, but has no online wheelchair
        \Laravel\Sanctum\Sanctum::actingAs($user, [\App\Enums\TokenAbilityEnum::ACCESS_TOKEN->value]);
        $response = $this->getJson("/api/wheelchairs/mapping-permission?floor_id={$floor->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.can_map', false);
        $response->assertJsonPath('data.reason', 'No active wheelchair connected.');

        // Add online wheelchair for user
        $wheelchair = \App\Models\Wheelchair::create([
            'serial_number' => 'SN_USER_1',
            'api_key' => 'key_user_1',
            'user_id' => $user->id,
            'connection_state' => 'online',
        ]);

        // Case 2: Owner of the building with online wheelchair
        \Laravel\Sanctum\Sanctum::actingAs($user, [\App\Enums\TokenAbilityEnum::ACCESS_TOKEN->value]);
        $response = $this->getJson("/api/wheelchairs/mapping-permission?floor_id={$floor->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.can_map', true);
        $response->assertJsonPath('data.wheelchair_id', $wheelchair->id);
        $response->assertJsonPath('data.reason', 'User is the owner of the building for this floor.');

        // Case 3: Other user with online wheelchair tries to check permission for this floor (not owned by them)
        $otherWheelchair = \App\Models\Wheelchair::create([
            'serial_number' => 'SN_USER_2',
            'api_key' => 'key_user_2',
            'user_id' => $otherUser->id,
            'connection_state' => 'online',
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($otherUser, [\App\Enums\TokenAbilityEnum::ACCESS_TOKEN->value]);
        $response = $this->getJson("/api/wheelchairs/mapping-permission?floor_id={$floor->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.can_map', false);
        $response->assertJsonPath('data.reason', 'User does not have permission to map this floor.');

        // Case 4: Super Admin can map anyway if they have an online wheelchair
        $admin = User::factory()->create(['role' => UserRoleEnum::SUPER_ADMIN->value]);
        $adminWheelchair = \App\Models\Wheelchair::create([
            'serial_number' => 'SN_ADMIN',
            'api_key' => 'key_admin',
            'user_id' => $admin->id,
            'connection_state' => 'online',
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($admin, [\App\Enums\TokenAbilityEnum::ACCESS_TOKEN->value]);
        $response = $this->getJson("/api/wheelchairs/mapping-permission?floor_id={$floor->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.can_map', true);
        $response->assertJsonPath('data.reason', 'Super admin has access to all floors.');
    }
}
