<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DisabilityType;
use App\Models\MedicalCondition;
use App\Models\Wheelchair;
use App\Models\WheelchairSensor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserMedicalAndWheelchairTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();



        // Seed some basic medical conditions
        MedicalCondition::create([
            'id' => 1,
            'name_en' => 'Heart Diseases',
            'name_ar' => 'أمراض القلب',
            'category_en' => 'Cardiovascular',
            'category_ar' => 'الأوعية الدموية والقلب',
            'description_en' => 'Chronic conditions affecting the heart and blood vessels.',
            'description_ar' => 'حالات مزمنة تؤثر على القلب والأوعية الدموية.'
        ]);

        MedicalCondition::create([
            'id' => 2,
            'name_en' => 'Diabetes',
            'name_ar' => 'السكري',
            'category_en' => 'Endocrine',
            'category_ar' => 'الغدد الصماء',
            'description_en' => 'Metabolic diseases causing high blood sugar.',
            'description_ar' => 'مرض أيضي يسبب ارتفاع نسبة السكر في الدم.'
        ]);
    }

    /**
     * Test public localized endpoints for disability types and medical conditions.
     */
    public function test_can_retrieve_localized_disability_types_and_medical_conditions()
    {
        // 2. Medical conditions EN
        $mcResponseEn = $this->getJson('/api/medical-conditions', ['Accept-Language' => 'en']);
        $mcResponseEn->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Heart Diseases');

        // Medical conditions AR
        $mcResponseAr = $this->getJson('/api/medical-conditions', ['Accept-Language' => 'ar']);
        $mcResponseAr->assertStatus(200)
            ->assertJsonPath('data.0.name', 'أمراض القلب');
    }

    /**
     * Test validation rules for user registration when role is 'user'.
     */
    public function test_user_role_registration_requires_medical_profile_fields()
    {
        $response = $this->postJson('/api/signup', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'user',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'username', 'gender',
                'birth_date', 'weight', 'height'
            ]);
    }

    /**
     * Test successful registration with user role and medical profile fields.
     */
    public function test_successful_user_registration_with_medical_profile()
    {
        $payload = [
            'name' => 'Valid User',
            'email' => 'valid@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'user',
            'phone' => '+201111111111',
            'username' => 'validuser',
            'gender' => 'male',
            'birth_date' => '2001-05-20',
            'weight' => 75.5,
            'height' => 178.0,
            'medical_condition_ids' => [1, 2]
        ];

        $response = $this->postJson('/api/signup', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'email' => 'valid@example.com',
            'username' => 'validuser',
            'gender' => 'male',
            'weight' => 75.5,
            'height' => 178.0,
        ]);

        $user = User::where('email', 'valid@example.com')->first();
        $this->assertEquals(25, $user->age);
        $this->assertCount(2, $user->medicalConditions);

        $this->assertDatabaseHas('user_medical_conditions', [
            'user_id' => $user->id,
            'medical_condition_id' => 1,
        ]);
    }

    /**
     * Test duplicate username and invalid severity validation failures.
     */
    public function test_registration_validation_fails_on_duplicate_username_or_invalid_severity()
    {
        // 1. Create a user first with username 'existinguser'
        User::factory()->create([
            'username' => 'existinguser'
        ]);

        // 2. Try registering another user with the duplicate username
        $payload = [
            'name' => 'Valid User',
            'email' => 'valid@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'user',
            'phone' => '+201111111111',
            'username' => 'existinguser', // duplicate
            'gender' => 'male',
            'birth_date' => '2001-05-20',
            'weight' => 75.5,
            'height' => 178.0,
            'medical_conditions' => [
                [
                    'medical_condition_id' => 1,
                    'severity' => 'extreme', // invalid severity
                    'notes' => 'Notes'
                ]
            ]
        ];

        $response = $this->postJson('/api/signup', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }



    /**
     * Test login using email as the login identifier.
     */
    public function test_can_login_using_email()
    {
        $user = User::factory()->create([
            'email' => 'login_email@example.com',
            'username' => 'login_username',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login_email@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token', 'user']]);
    }

    /**
     * Test login using username as the login identifier.
     */
    public function test_can_login_using_username()
    {
        $user = User::factory()->create([
            'email' => 'login_email2@example.com',
            'username' => 'login_username2',
            'password' => bcrypt('password123'),
        ]);

        // Using "username" field name
        $response1 = $this->postJson('/api/login', [
            'username' => 'login_username2',
            'password' => 'password123',
        ]);

        $response1->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token', 'user']]);

        // Or using "login" field name
        $response2 = $this->postJson('/api/login', [
            'login' => 'login_username2',
            'password' => 'password123',
        ]);

        $response2->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token', 'user']]);

        // Or using "email" field name containing the username
        $response3 = $this->postJson('/api/login', [
            'email' => 'login_username2',
            'password' => 'password123',
        ]);

        $response3->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token', 'user']]);
    }
}
