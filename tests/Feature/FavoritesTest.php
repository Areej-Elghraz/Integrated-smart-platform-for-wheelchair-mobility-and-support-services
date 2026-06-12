<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Place;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class FavoritesTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
    }

    public function test_user_can_list_favorites()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['access_token']);

        $category = Category::create(['name' => 'Test Category', 'owner_id' => $user->id]);
        
        $organization = \App\Models\Organization::create([
            'name' => 'Test Org',
            'owner_id' => $user->id,
            'description' => 'Test Description',
        ]);

        $place = Place::create([
            'name' => 'Favorite Place',
            'owner_id' => $user->id,
            'description' => 'A nice place',
            'x' => 10.0,
            'y' => 20.0,
            'points' => [['x' => 10.0, 'y' => 20.0], ['x' => 15.0, 'y' => 25.0]],
        ]);

        $user->favorites()->attach($place->id);

        $response = $this->getJson('/api/profile/favorites');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $place->id)
            ->assertJsonPath('data.0.name', 'Favorite Place');
    }

    public function test_favorites_pagination()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['access_token']);
        
        $category = Category::create(['name' => 'Test Category', 'owner_id' => $user->id]);
        
        $organization = \App\Models\Organization::create([
            'name' => 'Test Org',
            'owner_id' => $user->id,
            'description' => 'Test Description',
        ]);

        $places = [];
        for ($i = 0; $i < 16; $i++) {
            $place = Place::create([
                'name' => "Place $i",
                'owner_id' => $user->id,
                'x' => 0, 
                'y' => 0
            ]);
            $user->favorites()->attach($place->id);
            $places[] = $place;
        }

        $response = $this->getJson('/api/profile/favorites?pagination=5');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(16, $response->json('total'));
    }
}
