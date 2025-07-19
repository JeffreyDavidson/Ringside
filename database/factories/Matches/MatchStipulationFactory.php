<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matches\MatchStipulation>
 */
class MatchStipulationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Ladder Match',
            'Cage Match',
            'No Holds Barred',
            'Last Man Standing',
            'Table Match',
            'Hardcore Match',
            'Steel Chair Match',
            'Falls Count Anywhere',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Configure the factory to create an active stipulation.
     */
    public function active(): static
    {
        return $this->state([
            'is_active' => true,
        ]);
    }

    /**
     * Configure the factory to create an inactive stipulation.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}