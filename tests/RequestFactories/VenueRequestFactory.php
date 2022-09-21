<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use Worksome\RequestFactories\RequestFactory;

class VenueRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'street_address' => $this->faker->buildingNumber().' '.$this->faker->streetName(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip' => (string) mt_rand(11111, 99999),
        ];
    }
}
