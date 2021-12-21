<?php

namespace App\Enums;

use Closure;
use Spatie\Enum\Laravel\Enum;

class BaseEnum extends Enum
{
    protected static function values(): Closure
    {
        return fn (string $name): string => str_replace('_', '-', $name);
    }
}
