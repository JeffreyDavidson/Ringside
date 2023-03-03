<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\SingleRosterMember;
use Exception;

class CannotBeRetiredException extends Exception
{
    public static function unemployed(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is unemployed and cannot be retired.");
    }

    public static function hasFutureEmployment(SingleRosterMember $model):  self
    {
        return new static("`{$model->name}` has not been officially employed and cannot be retired.");
    }

    public static function retired(SingleRosterMember $model):  self
    {
        return new static("`{$model->name}` is already retired.");
    }
}
