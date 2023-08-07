<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Suspendable;
use Exception;

class CannotBeReinstatedException extends Exception
{
    public static function unemployed(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is unemployed and cannot be reinstated.");
    }

    public static function released(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is released and cannot be reinstated.");
    }

    public static function retired(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is retired and cannot be reinstated.");
    }

    public static function hasFutureEmployment(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` has not been officially employed and cannot be reinstated.");
    }

    public static function bookable(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is bookable and cannot be reinstated.");
    }

    public static function injured(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is injured and cannot be reinstated.");
    }

    public static function available(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is available and cannot be reinstated.");
    }

    public static function unbookable(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is unbookable and cannot be reinstated.");
    }
}
