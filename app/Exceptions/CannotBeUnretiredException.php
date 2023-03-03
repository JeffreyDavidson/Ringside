<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\SingleRosterMember;
use Exception;

class CannotBeUnretiredException extends Exception
{
    public static function notRetired(SingleRosterMember $model): self
    {
        return new static("`{$model->name}` is not retired and cannot be unretired.");
    }
}
