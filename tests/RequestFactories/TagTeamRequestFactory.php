<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use Illuminate\Support\Str;
use Worksome\RequestFactories\RequestFactory;

class TagTeamRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'name' => Str::title($this->faker->words(3, true)),
            'signature_move' => null,
            'start_date' => null,
            'wrestlerA' => null,
            'wrestlerB' => null,
        ];
    }
}
