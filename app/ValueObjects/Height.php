<?php

declare(strict_types=1);

namespace App\ValueObjects;

class Height
{
    public function __construct(public int $feet, public int $inches)
    {
    }

    public function __toString(): string
    {
        return "{$this->feet}'{$this->inches}\"";
    }
}
