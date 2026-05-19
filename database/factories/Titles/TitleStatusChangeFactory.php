<?php

declare(strict_types=1);

namespace Database\Factories\Titles;

use App\Enums\Shared\ActivationStatus;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Titles\TitleStatusChange>
 */
class TitleStatusChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_id' => Title::factory(),
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

    /**
     * Configure the factory to create an inactive status change.
     */
    public function inactive(): static
    {
        return $this->state([
            'status' => ActivationStatus::Inactive,
        ]);
    }

    /**
     * Configure the factory to create an active status change.
     */
    public function active(): static
    {
        return $this->state([
            'status' => ActivationStatus::Active,
        ]);
    }

    /**
     * Configure the factory to create a retired status change.
     */
    public function retired(): static
    {
        return $this->state([
            'status' => ActivationStatus::Retired,
        ]);
    }

    /**
     * Configure the factory to create an unactivated status change.
     */
    public function unactivated(): static
    {
        return $this->state([
            'status' => ActivationStatus::Unactivated,
        ]);
    }
}
