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
            'address1' => $this->faker->buildingNumber(),
            'address2' => $this->faker->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip' => $this->faker->postcode(),
        ];
    }
}
