<?php

declare(strict_types=1);

namespace Database\Factories\Users;

use App\Enums\Users\Role;
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'secret',
            'remember_token' => Str::random(10),
            'role' => Role::Basic,
            'avatar_path' => '300-3.png',
            'phone_number' => fake()->unique()->phoneNumber(),
        ];
    }

    /**
     * Indicates the user should be an administrator.
     */
    public function administrator(): static
    {
        return $this->state([
            'role' => Role::Administrator,
        ]);
    }

    /**
     * Indicates the user should be a normal user.
     */
    public function basicUser(): static
    {
        return $this->state([
            'role' => Role::Basic,
        ]);
    }

    /**
     * Indicates the user should be unverified.
     */
    public function unverified(): static
    {
        return $this->state([
            'email_verified_at' => null,
        ]);
    }
}
