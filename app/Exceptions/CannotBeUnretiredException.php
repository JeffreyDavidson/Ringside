<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Retirable;
use Exception;

class CannotBeUnretiredException extends Exception
{
    public static function notRetired(Retirable $model): self
    {
        return new static("`{$model->name}` is not retired and cannot be unretired.");
    }
}
