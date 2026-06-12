<?php

namespace Tests\Feature;

use App\Enums\TokenAbilityEnum;
use App\Models\Category;
use App\Models\Floor;
use App\Models\Map;
use App\Models\Organization;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FloorsAndMapsTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate($user = null)
    {
        $user = $user ?: User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbilityEnum::ACCESS_TOKEN->value])->plainTextToken;
        return [$user, ['Authorization' => 'Bearer ' . $token]];
    }

    protected function setupDeps($user)
    {
        $category = Category::create(['name' => 'Test Category', 'owner_id' => $user->id]);
        $organization = Organization::create([
            'name' => 'Test Org',
            'owner_id' => $user->id,
            'description' => 'A test organization',
        ]);
        $building = \App\Models\Building::create([
            'name' => 'Test Building',
            'organization_id' => $organization->id,
        ]);
        $place = Place::create([
            'name' => 'Test Place',
            'owner_id' => $user->id,
            'x' => 10.0,
            'y' => 20.0,
            'points' => [['x' => 10.0, 'y' => 20.0], ['x' => 15.0, 'y' => 25.0]],
        ]);
        $place->categories()->attach($category->id);

        return [$category, $organization, $place, $building];
    }

    public function test_can_manage_building_floors()
    {
        [$user, $headers] = $this->authenticate();
        [$category, $organization, $place, $building] = $this->setupDeps($user);

        // 1. Create a Floor for Building
        $response = $this->postJson("/api/buildings/{$building->id}/floors", [
            'name' => 'Ground Floor',
            'number' => 1,
        ], $headers);

        $response->assertStatus(201)
                 ->assertJsonPath('data.name', 'Ground Floor')
                 ->assertJsonPath('data.number', 1);

        $floorId = $response->json('data.id');

        $this->assertDatabaseHas('floors', [
            'id' => $floorId,
            'building_id' => $building->id,
            'name' => 'Ground Floor',
            'number' => 1,
        ]);

        // 2. List Building Floors
        $response = $this->getJson("/api/buildings/{$building->id}/floors", $headers);
        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.name', 'Ground Floor');

        // 3. Show Floor
        $response = $this->getJson("/api/floors/{$floorId}", $headers);
        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Ground Floor');

        // 4. Update Floor
        $response = $this->putJson("/api/floors/{$floorId}", [
            'name' => 'Ground Level',
        ], $headers);

        $response->assertStatus(200)
                 ->assertJsonPath('data.name', 'Ground Level');

        $this->assertDatabaseHas('floors', [
            'id' => $floorId,
            'name' => 'Ground Level',
        ]);

        // 5. Delete Floor
        $response = $this->deleteJson("/api/floors/{$floorId}", [], $headers);
        $response->assertStatus(200);

        $this->assertDatabaseMissing('floors', [
            'id' => $floorId,
        ]);
    }

    public function test_can_upload_and_delete_floor_maps()
    {
        Storage::fake('public');
        [$user, $headers] = $this->authenticate();
        [$category, $organization, $place, $building] = $this->setupDeps($user);

        $floor = Floor::create([
            'building_id' => $building->id,
            'name' => 'Ground Floor',
            'number' => 1,
        ]);

        // 1. Upload Map
        $file = UploadedFile::fake()->image('map.png');
        $response = $this->postJson("/api/floors/{$floor->id}/map", [
            'map_file' => $file,
            'width' => 100.5,
            'height' => 80.2,
            'resolution' => 0.05,
            'origin' => [-10.0, -10.0, 0.0],
        ], $headers);

        $response->assertStatus(201)
                 ->assertJsonPath('data.width', 100.5)
                 ->assertJsonPath('data.height', 80.2)
                 ->assertJsonPath('data.yaml_data.resolution', 0.05)
                 ->assertJsonPath('data.yaml_data.origin.0', -10);

        $mapPath = $response->json('data.map_file');
        // Clean map path to relative for Storage assert
        $relativePath = str_replace(asset('storage/'), '', $mapPath);
        Storage::disk('public')->assertExists($relativePath);

        // 2. Show Map
        $response = $this->getJson("/api/floors/{$floor->id}/map", $headers);
        $response->assertStatus(200)
                 ->assertJsonPath('data.width', 100.5);

        // 3. Delete Map
        $response = $this->deleteJson("/api/floors/{$floor->id}/map", [], $headers);
        $response->assertStatus(200);

        Storage::disk('public')->assertMissing($relativePath);
        $this->assertDatabaseMissing('maps', [
            'floor_id' => $floor->id,
        ]);
    }

    public function test_coordinates_validation_on_place_creation()
    {
        [$user, $headers] = $this->authenticate();
        [$category, $organization, $place, $building] = $this->setupDeps($user);

        // Create a floor
        $floor = Floor::create([
            'building_id' => $building->id,
            'name' => 'Floor 1',
            'number' => 1,
        ]);

        // 1. Try to create place without points (fails)
        $response = $this->postJson("/api/floors/{$floor->id}/places", [
            'name' => 'New Place',
            'category_id' => $category->id,
            'country_name' => 'Egypt',
            'city_name' => 'Cairo',
            'image' => UploadedFile::fake()->image('place.png'),
            'x' => 12.5,
            'y' => 34.6,
        ], $headers);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['points']);

        // 2. Try to create place with invalid points structure (fails)
        $response = $this->postJson("/api/floors/{$floor->id}/places", [
            'name' => 'New Place',
            'category_id' => $category->id,
            'country_name' => 'Egypt',
            'city_name' => 'Cairo',
            'image' => UploadedFile::fake()->image('place.png'),
            'points' => [['x' => 10]], // missing y
        ], $headers);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['points.0.y']);

        // 3. Create place with points, no x, y (succeeds, calculates center)
        $response = $this->postJson("/api/floors/{$floor->id}/places", [
            'name' => 'New Place',
            'category_id' => $category->id,
            'country_name' => 'Egypt',
            'city_name' => 'Cairo',
            'image' => UploadedFile::fake()->image('place.png'),
            'points' => [['x' => 10, 'y' => 10], ['x' => 20, 'y' => 20]],
            'z' => 1.2,
            'rotation' => 90.0,
        ], $headers);

        $response->assertStatus(201)
                 ->assertJsonPath('data.x', 15) // (10+20)/2 = 15
                 ->assertJsonPath('data.y', 15) // (10+20)/2 = 15
                 ->assertJsonPath('data.z', 1.2)
                 ->assertJsonPath('data.rotation', 90)
                 ->assertJsonPath('data.floor.id', $floor->id);
    }
}
