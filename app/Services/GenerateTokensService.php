<?php

namespace App\Services;

use App\Models\User;
use App\TokenAbilityEnum;

class GenerateTokensService
{
  public function __invoke(User $user, string $deviceName, bool $remember)
  {
    $user->tokens()?->where('name', $user->currentAccessToken()?->name)
      ->orWhere('name', 'access_token_' . $deviceName)
      ->orWhere('name', 'remember_token_' . $deviceName)
      ->delete();

    // $user->tokens()->where('expires_at', '<', now())->delete();

    $accessToken = $user->createToken('access_token_' . $deviceName . '_' . $user->email, [TokenAbilityEnum::ACCESS_TOKEN->value], now()->addSeconds(config('sanctum.access_expiration')))->plainTextToken;
    if ($remember == 1) {
      $rememberToken = $user->createToken('remember_token_' . $deviceName . '_' . $user->email, [TokenAbilityEnum::REMEMBER_TOKEN->value], now()->addSeconds(config('sanctum.remember_expiration')))->plainTextToken;
    }
    return [
      'access_token'              => $accessToken,
      'access_token_expiration'   => config('sanctum.access_expiration'),
      'remember_token'            => $rememberToken,
      'remember_token_expiration' => config('sanctum.remember_expiration'),
    ];
  }
}
