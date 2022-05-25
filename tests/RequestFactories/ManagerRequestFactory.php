<?php

declare(strict_types=1);

namespace Tests\RequestFactories;

use Worksome\RequestFactories\RequestFactory;

class ManagerRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'started_at' => null,
        ];
    }
}
