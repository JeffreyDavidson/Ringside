<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'secret',
            'remember_token' => Str::random(10),
            'role' => Role::BASIC,
        ];
    }

    /**
     * Indicates the user should be an administrator.
     */
    public function administrator(): static
    {
        return $this->state([
            'role' => Role::ADMINISTRATOR,
        ]);
    }

    /**
     * Indicates the user should be a normal user.
     */
    public function basicUser(): static
    {
        return $this->state([
            'role' => Role::BASIC,
        ]);
    }
}
