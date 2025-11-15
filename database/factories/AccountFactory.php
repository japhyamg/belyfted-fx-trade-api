<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true) . ' Account',
            'currency' => fake()->randomElement(['GBP', 'EUR', 'USD', 'NGN']),
            'balance' => fake()->randomFloat(2, 100, 10000),
            'status' => 'active',
        ];
    }
}
