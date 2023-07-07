<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Injurable;
use Exception;

class CannotBeClearedFromInjuryException extends Exception
{
    public static function notInjured(Injurable $model): self
    {
        return new self("`{$model->name}` is not injured and cannot be cleared from an injury.");
    }
}
