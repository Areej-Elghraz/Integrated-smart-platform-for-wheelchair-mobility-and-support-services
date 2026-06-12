<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class LocalizationCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_messages_are_localized()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['access_token']);

        $languages = [
            'en' => 'Categories retrieved successfully!',
            'ar' => 'تم جلب التصنيفات بنجاح!',
            'de' => 'Kategorien erfolgreich abgerufen!',
            'fr' => 'Catégories récupéré(e) avec succès !',
            'hi' => 'श्रेणियाँ सफलतापूर्वक प्राप्त किया गया!',
            'ko' => '카테고리 검색 성공!',
            'vi' => 'Đã lấy Danh mục thành công!',
        ];

        foreach ($languages as $lang => $expectedMessage) {
            $response = $this->getJson('/api/categories', [
                'Accept-Language' => $lang
            ]);

            $response->assertStatus(200);
            $this->assertEquals($expectedMessage, $response->json('message'), "Failed for language: $lang");
        }
    }

    public function test_place_description_is_returned()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['access_token']);

        $category = Category::create(['name' => 'Test Category', 'owner_id' => $user->id]);
        $organization = \App\Models\Organization::create(['name' => 'Test Org', 'owner_id' => $user->id]);

        $place = \App\Models\Place::create([
            'name' => 'Descriptive Place',
            'description' => 'This is a test description for the place.',
            'owner_id' => $user->id,
            'x' => 0,
            'y' => 0
        ]);
        $place->categories()->attach($category->id);

        $user->favorites()->attach($place->id);

        $response = $this->getJson('/api/profile/favorites');
        $response->assertStatus(200);
        $this->assertEquals('This is a test description for the place.', $response->json('data.0.description'));
    }
}
