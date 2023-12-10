<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Activatable;
use Exception;

class CannotBeActivatedException extends Exception
{
    public static function activated(Activatable $model): self
    {
        return new self("`{$model->getIdentifier()}` is already activated.");
    }
}
