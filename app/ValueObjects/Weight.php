<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

readonly class Weight
{
    public function __construct(public int $pounds)
    {
        if ($pounds <= 0) {
            throw new InvalidArgumentException('Weight must be greater than zero.');
        }
    }

    public function __toString(): string
    {
        return "{$this->pounds} lbs";
    }

    public function toPounds(): int
    {
        return $this->pounds;
    }
}
