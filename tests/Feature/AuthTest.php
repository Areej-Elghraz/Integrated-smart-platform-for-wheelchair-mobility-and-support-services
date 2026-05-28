<?php

namespace Tests\Feature;

use App\Enums\LanguagePreferenceEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_language_preference()
    {
        $response = $this->postJson('/api/signup', [ // Corrected route
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'policies' => true,
            'language' => LanguagePreferenceEnum::AR->value,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'language' => LanguagePreferenceEnum::AR->value,
        ]);
    }

    public function test_user_cache_is_created_on_login_and_retrieval()
    {
        $user = User::factory()->create([
            'email' => 'cache@example.com',
            'password' => bcrypt('password'),
            'language' => LanguagePreferenceEnum::EN->value,
            'email_verified_at' => now(), 
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($user);

        $response = $this->postJson('/api/login', [ // Corrected route
            'email' => 'cache@example.com',
            'password' => 'password',
            'remember' => false,
        ]);

        $response->assertStatus(200);
    }

    public function test_response_is_localized_based_on_user_preference()
    {
        $user = User::factory()->create([
            'email' => 'ar@example.com',
            'password' => bcrypt('password'),
            'language' => LanguagePreferenceEnum::AR->value,
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('test-token', [\App\Enums\TokenAbilityEnum::ACCESS_TOKEN->value])->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/logout');
            
        $response->assertStatus(200);
        
        // Assert the message is in Arabic (logout_success from ar/auth.php)
        // Assuming ar/auth.php has 'logout_success' => 'تم تسجيل الخروج بنجاح.' or similar.
        // We will match against the translation key value.
        $startOfArabicMessage = substr(__('auth.logout_success', [], 'ar'), 0, 10);
        $this->assertStringContainsString($startOfArabicMessage, $response->json('message'));
    }

    public function test_response_uses_header_if_not_authenticated()
    {
        User::factory()->create([
             'email' => 'wrong@example.com',
             'password' => bcrypt('password'),
        ]);

        // Test with invalid credentials to trigger auth.failed
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com', 
            'password' => 'wrongpass',
            'remember' => false,
        ], ['Accept-Language' => 'ar']);

        $response->assertStatus(401);
        
        // Assert the message is in Arabic (failed from ar/auth.php)
        $expectedMessage = __('auth.failed', [], 'ar');
        $this->assertEquals($expectedMessage, $response->json('message'));
    }

}
