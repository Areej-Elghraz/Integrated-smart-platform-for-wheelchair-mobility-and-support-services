<?php

namespace Tests\Feature;

use App\Enums\TokenAbilityEnum;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PlaceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', [TokenAbilityEnum::ACCESS_TOKEN->value])->plainTextToken;
        return [$user, ['Authorization' => 'Bearer ' . $token]];
    }

    protected function createPlaceDeps()
    {
        $category = Category::create(['name' => 'Test Category']);
        $organization = Organization::create(['name' => 'Test Org']);
        return [$category, $organization];
    }

    public function test_can_review_place()
    {
        [$user, $headers] = $this->authenticate();
        [$category, $organization] = $this->createPlaceDeps();

        $place = Place::create([
            'name' => 'Test Place',
            'x' => 10,
            'y' => 10,
        ]);
        $place->categories()->attach($category->id);

        $response = $this->postJson("/api/places/{$place->id}/reviews", [
            'rating' => 5,
            'comment' => 'Great place!',
        ], $headers);

        $response->assertStatus(201)
                 ->assertJsonPath('data.rating', 5)
                 ->assertJsonPath('data.comment', 'Great place!');

        $this->assertDatabaseHas('reviews', [
            'reviewable_id' => $place->id,
            'reviewable_type' => Place::class,
            'user_id' => $user->id,
            'rating' => 5,
        ]);
    }

    public function test_place_resource_includes_ratings()
    {
        [$user, $headers] = $this->authenticate();
        [$category, $organization] = $this->createPlaceDeps();

        $place = Place::create([
            'name' => 'Rated Place',
            'owner_id' => $user->id,
        ]);
        
        // Create reviews
        $place->reviews()->create(['user_id' => $user->id, 'rating' => 5, 'comment' => 'Best']);
        $otherUser = User::factory()->create();
        $place->reviews()->create(['user_id' => $otherUser->id, 'rating' => 3, 'comment' => 'Average']);

        $response = $this->getJson("/api/places/{$place->id}", $headers);
        
        $response->dump();

        $response->assertStatus(200)
                 ->assertJsonPath('data.rating', 4) // (5+3)/2 = 4
                 ->assertJsonPath('data.rating_distribution.5', '50%')
                 ->assertJsonPath('data.rating_distribution.3', '50%');
    }

    public function test_can_favorite_place()
    {
        [$user, $headers] = $this->authenticate();
        [$category, $organization] = $this->createPlaceDeps();

        $place = Place::create([
            'name' => 'Fav Place',
        ]);
        $place->categories()->attach($category->id);

        // Toggle On
        $response = $this->postJson("/api/places/{$place->id}/favorite", [], $headers);
        $response->assertStatus(200)
                 ->assertJsonPath('data.is_favorited', true);
        
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'favoritable_id' => $place->id,
            'favoritable_type' => Place::class,
        ]);

        // Toggle Off
        $response = $this->postJson("/api/places/{$place->id}/favorite", [], $headers);
        $response->assertStatus(200)
                 ->assertJsonPath('data.is_favorited', false);
        
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'favoritable_id' => $place->id,
            'favoritable_type' => Place::class,
        ]);
    }

    public function test_can_delete_review()
    {
        [$user, $headers] = $this->authenticate();
        [$category, $organization] = $this->createPlaceDeps();

        $place = Place::create([
            'name' => 'Place',
        ]);
        $place->categories()->attach($category->id);
        $review = $place->reviews()->create(['user_id' => $user->id, 'rating' => 4]);

        $response = $this->deleteJson("/api/reviews/{$review->id}", [], $headers);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_cannot_delete_others_review()
    {
        [$user, $headers] = $this->authenticate();
        [$category, $organization] = $this->createPlaceDeps();

        $otherUser = User::factory()->create();
        $place = Place::create([
            'name' => 'Place',
        ]);
        $place->categories()->attach($category->id);
        $review = $place->reviews()->create(['user_id' => $otherUser->id, 'rating' => 4]);

        $response = $this->deleteJson("/api/reviews/{$review->id}", [], $headers);

        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }
}
