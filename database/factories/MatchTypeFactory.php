<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'name' => Str::of($name)->title(),
            'slug' => Str::of($name)->slug(),
            'number_of_sides' => $this->faker->randomDigit(),
        ];
    }
}
