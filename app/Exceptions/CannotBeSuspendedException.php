<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\SingleRosterMember;
use Exception;

class CannotBeSuspendedException extends Exception
{
    public static function unemployed(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is unemployed and cannot be suspended.");
    }

    public static function hasFutureEmployment(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` has not been officially employed and cannot be suspended.");
    }

    public static function retired(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is retired and cannot be suspended.");
    }

    public static function released(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is released and cannot be suspended.");
    }

    public static function suspended(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is already suspended.");
    }

    public static function injured(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is injured and cannot be suspended.");
    }
}
