<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use Illuminate\Support\Str;
use Worksome\RequestFactories\RequestFactory;

class EventRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'name' => Str::title($this->faker->words(3, true)),
            'date' => null,
            'venue_id' => null,
            'preview' => null,
        ];
    }
}
