<?php

namespace App\Enums;

use Closure;
use Spatie\Enum\Laravel\Enum;

class BaseEnum extends Enum
{
    protected static function values(): Closure
    {
        return function (string $name): string {
            return str_replace('_', '-', $name);
        };
    }
}
