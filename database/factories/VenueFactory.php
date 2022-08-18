<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(),
            'street_address' => $this->faker->buildingNumber().' '.$this->faker->streetName(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip' => str($this->faker->postcode())->substr(0, 5),
        ];
    }
}
