<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Suspendable;
use App\Models\SingleRosterMember;
use Exception;

class CannotBeReinstatedException extends Exception
{
    public static function unemployed(Suspendable $model): self
    {
        return new static("`{$model->name}` is unemployed and cannot be reinstated.");
    }

    public static function released(Suspendable $model): self
    {
        return new static("`{$model->name}` is released and cannot be reinstated.");
    }

    public static function retired(Suspendable $model): self
    {
        return new static("`{$model->name}` is retired and cannot be reinstated.");
    }

    public static function hasFutureEmployment(Suspendable $model): self
    {
        return new static("`{$model->name}` has not been officially employed and cannot be reinstated.");
    }

    public static function bookable(Suspendable $model): self
    {
        return new static("`{$model->name}` is bookable and cannot be reinstated.");
    }

    public static function injured(Suspendable $model): self
    {
        return new static("`{$model->name}` is injured and cannot be reinstated.");
    }
}
