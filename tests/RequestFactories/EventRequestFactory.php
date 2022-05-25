<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use Worksome\RequestFactories\RequestFactory;

class EventRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'date' => null,
            'venue_id' => null,
            'preview' => null,
        ];
    }
}
