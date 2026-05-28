<?php

namespace App\Services;

use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Enums\LanguagePreferenceEnum;

class UserService
{
    protected $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data, ?string $imagePath): User
    {
        $role = $data['role'] ?? UserRoleEnum::USER->value;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $role,
            'password' => Hash::make($data['password']),
            'language' => $data['language'] ?? LanguagePreferenceEnum::EN->value,
            // user
            'phone' => $data['phone'] ?? null,
            'follow_doctor' => $data['follow_doctor'] ?? null,
            'username' => $data['username'] ?? null,
            'gender' => $data['gender'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'blood_type' => $data['blood_type'] ?? null,
            'weight' => $data['weight'] ?? null,
            'height' => $data['height'] ?? null,
            // organization
            'image' => $imagePath ?? null,
        ]);

        if ($role === UserRoleEnum::ORGANIZATION->value) {
            $this->organizationService->createOrganization($user, [
                'name' => $data['name'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'country_name' => $data['country_name'] ?? null,
                'city_name' => $data['city_name'] ?? null,
                'image' => $imagePath,
                'category_id' => $data['category_id'] ?? null,
                'category_name' => $data['category_name'] ?? 'General',
                'description' => $data['description'] ?? null,
            ], $imagePath);
        }

        return $user;
    }

    /**
     * Get user by email with caching.
     */
    public function getUserByEmail(string $email): ?User
    {
        return Cache::remember("user_email:{$email}", 3600, function () use ($email) {
            return User::where('email', $email)->first();
        });
    }

    /**
     * Clear user cache.
     */
    public function clearUserCache(User $user): void
    {
        Cache::forget("user_email:{$user->email}");
    }

    /**
     * Update user and clear cache.
     */
    public function updateUser(User $user, array $data): bool
    {
        $updated = $user->update($data);
        if ($updated) {
            $this->clearUserCache($user);
        }
        return $updated;
    }
}
