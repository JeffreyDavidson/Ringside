<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use Illuminate\Support\Str;
use Worksome\RequestFactories\RequestFactory;

class TitleRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'name' => Str::of($this->faker->name())->append(' Title')->value,
            'activated_at' => null,
        ];
    }
}
