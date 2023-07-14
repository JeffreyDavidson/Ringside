<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title($this->faker->words(2, true));

        return [
            'name' => Str::of($name)->append(' Arena')->value(),
            'street_address' => $this->faker->buildingNumber().' '.$this->faker->streetName(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip' => str($this->faker->postcode())->substr(0, 5)->value(),
        ];
    }
}
