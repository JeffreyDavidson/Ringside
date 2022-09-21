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
            'name' => Str::of(Str::title($this->faker->words(2, true)))->append(' Title')->value,
            'activated_at' => null,
        ];
    }
}
