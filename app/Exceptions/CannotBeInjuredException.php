<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\SingleRosterMember;
use Exception;

class CannotBeInjuredException extends Exception
{
    public static function unemployed(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is unemployed and cannot be injured.");
    }

    public static function released(SingleRosterMember $model):  self
    {
        return new static("`{$model->name}` is released and cannot be injured.");
    }

    public static function retired(SingleRosterMember $model):  self
    {
        return new static("`{$model->name}` is retired and cannot be injured.");
    }

    public static function hasFutureEmployment(SingleRosterMember $model):  self
    {
        return new static("`{$model->name}` has not been officially employed and cannot be injured.");
    }

    public static function injured(SingleRosterMember $model):  self
    {
        return new static("`{$model->displayName}` is already currently injured.");
    }

    public static function suspended(SingleRosterMember $model):  self
    {
        return new static("`{$model->name}` is suspended and cannot be injured.");
    }
}
