<?php
// Create an unverified test user
use App\Models\User;
use Illuminate\Support\Facades\Hash;

User::where('email', 'testverify@example.com')->delete();

$user = User::create([
    'name' => 'Test Verify',
    'email' => 'testverify@example.com',
    'password' => Hash::make('password123'),
    'role' => 'user',
    'email_verified_at' => null,
]);

echo "Created unverified user ID: {$user->id}\n";
echo "email_verified_at: " . ($user->email_verified_at ?? 'NULL') . "\n";
