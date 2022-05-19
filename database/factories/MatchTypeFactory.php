<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MatchTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => str($name)->title(),
            'slug' => str($name)->slug(),
            'number_of_sides' => $this->faker->randomDigit(),
        ];
    }
}
