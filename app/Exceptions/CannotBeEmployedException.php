<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\SingleRosterMember;
use Exception;

class CannotBeEmployedException extends Exception
{
    public static function employed(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is already employed.");
    }
}
