<?php

declare(strict_types=1);

namespace App\Enums;

use Closure;
use Spatie\Enum\Laravel\Enum;

class BaseEnum extends Enum
{
    public function getBadgeColor()
    {
        return $this->colors[$this->value];
    }

    protected static function values(): Closure
    {
        return fn (string $name): string => str_replace('_', '-', $name);
    }
}
