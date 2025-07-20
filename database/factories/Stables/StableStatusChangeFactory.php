<?php

declare(strict_types=1);

namespace Database\Factories\Stables;

use App\Enums\Shared\ActivationStatus;
use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stables\StableStatusChange>
 */
class StableStatusChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stable_id' => Stable::factory(),
            'status' => fake()->randomElement(ActivationStatus::cases()),
            'changed_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Configure the factory to create an activation status change.
     */
    public function activated(): static
    {
        return $this->state([
            'status' => ActivationStatus::Active,
        ]);
    }

    /**
     * Configure the factory to create a deactivation status change.
     */
    public function deactivated(): static
    {
        return $this->state([
            'status' => ActivationStatus::Inactive,
        ]);
    }
}