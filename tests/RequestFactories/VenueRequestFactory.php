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
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip' => $this->faker->postcode(),
        ];
    }
}
