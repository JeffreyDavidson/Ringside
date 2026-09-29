<?php

declare(strict_types=1);

use Faker\Generator;
use Faker\Provider\Base;

/**
 * Make every `fake()->boolean()` call return the given outcome for the current test.
 *
 * The application's Faker instance is a container singleton, so the override
 * disappears with the application after the test.
 */
function forceFakerBoolean(bool $outcome): void
{
    fake()->addProvider(new class(fake(), $outcome) extends Base
    {
        public function __construct(Generator $generator, private readonly bool $outcome)
        {
            parent::__construct($generator);
        }

        public function boolean(int $chanceOfGettingTrue = 50): bool
        {
            return $this->outcome;
        }
    });
}
