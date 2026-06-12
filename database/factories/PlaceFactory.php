<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Place>
 */
class PlaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->address(),
            'description' => fake()->paragraph(),
            'owner_id' => \App\Models\User::factory(),
        ];
    }
}
