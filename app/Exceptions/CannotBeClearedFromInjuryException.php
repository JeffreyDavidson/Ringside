<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\SingleRosterMember;
use Exception;

class CannotBeClearedFromInjuryException extends Exception
{
    public static function notInjured(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is not injured and cannot be cleared from an injury.");
    }
}
