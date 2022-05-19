<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
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
            'role' => Role::basic(),
        ];
    }

    public function administrator(): self
    {
        return $this->state([
            'role' => Role::administrator(),
        ]);
    }

    public function basicUser(): self
    {
        return $this->state([
            'role' => Role::basic(),
        ]);
    }

    public function withRole($role)
    {
        return $this->state([
            'role' => $role,
        ]);
    }
}
