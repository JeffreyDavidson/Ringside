<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Activatable;
use Exception;

class CannotBeDeactivatedException extends Exception
{
    public static function unactivated(Activatable $model): self
    {
        return new static("`{$model->name}` is unemployed and cannot be released.");
    }

    public static function inactive(Activatable $model): self
    {
        return new static("`{$model->name}` is already inactive.");
    }

    public static function retired(Activatable $model): self
    {
        return new static("`{$model->name}` is retired and cannot be released.");
    }

    public static function hasFutureActivation(Activatable $model): self
    {
        return new static("`{$model->name}` has not been officially activated and cannot be deactivated.");
    }
}
