<?php

declare(strict_types=1);

namespace Database\Factories\Stables;

use App\Enums\Stables\StableStatus;
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
            'status' => fake()->randomElement(StableStatus::cases()),
            'changed_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Configure the factory to create an activation status change.
     */
    public function activated(): static
    {
        return $this->state([
            'status' => StableStatus::Active,
        ]);
    }

    /**
     * Configure the factory to create a deactivation status change.
     */
    public function deactivated(): static
    {
        return $this->state([
            'status' => StableStatus::Inactive,
        ]);
    }

    /**
     * Configure the factory to create an active status change.
     */
    public function active(): static
    {
        return $this->activated();
    }

    /**
     * Configure the factory to create an inactive status change.
     */
    public function inactive(): static
    {
        return $this->deactivated();
    }

    /**
     * Configure the factory to create a retired status change.
     */
    public function retired(): static
    {
        return $this->state([
            'status' => StableStatus::Retired,
        ]);
    }

    /**
     * Configure the factory to create an unactivated status change.
     */
    public function unactivated(): static
    {
        return $this->state([
            'status' => StableStatus::Unformed,
        ]);
    }

    /**
     * Configure the factory to create a pending establishment status change.
     */
    public function pendingEstablishment(): static
    {
        return $this->state([
            'status' => StableStatus::PendingEstablishment,
        ]);
    }
}
