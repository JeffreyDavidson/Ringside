<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use Illuminate\Support\Str;
use Worksome\RequestFactories\RequestFactory;

class StableRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'name' => Str::title($this->faker->words(3, true)),
            'start_date' => null,
            'wrestlers' => [],
            'tag_teams' => [],
        ];
    }
}
