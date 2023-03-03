<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\SingleRosterMember;
use Exception;

class CannotBeReleasedException extends Exception
{
    public static function unemployed(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is unemployed and cannot be released.");
    }

    public static function released(SingleRosterMember $model):  self
    {
        return new static("`{$model->name}` is alrady released.");
    }

    public static function retired(SingleRosterMember $model):  self
    {
        return new static("`{$model->name}` is retired and cannot be released.");
    }

    public static function hasFutureEmployment(SingleRosterMember $model):  self
    {
        return new static("`{$model->name}` has not been officially employed and cannot be released.");
    }
}
