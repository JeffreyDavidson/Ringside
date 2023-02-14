<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MatchDecisionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => str($name)->title(),
            'slug' => str($name)->slug(),
        ];
    }
}
