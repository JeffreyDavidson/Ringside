<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use Worksome\RequestFactories\RequestFactory;

class WrestlerRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'feet' => $this->faker->numberBetween(5, 8),
            'inches' => $this->faker->numberBetween(0, 11),
            'weight' => $this->faker->numberBetween(200, 400),
            'hometown' => $this->faker->city().', '.$this->faker->state(),
            'signature_move' => null,
            'start_date' => null,
        ];
    }
}
