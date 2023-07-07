<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Employable;
use Exception;

class CannotBeEmployedException extends Exception
{
    public static function employed(Employable $model): self
    {
        return new self("`{$model->name}` is already employed.");
    }
}
