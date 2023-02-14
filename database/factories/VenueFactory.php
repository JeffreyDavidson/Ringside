<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => Str::of(Str::title($this->faker->words(2, true)))->append(' Arena')->value,
            'street_address' => $this->faker->buildingNumber().' '.$this->faker->streetName(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip' => str($this->faker->postcode())->substr(0, 5),
        ];
    }
}
