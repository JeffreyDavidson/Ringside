<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBePulledException extends BaseBusinessException
{
    public static function notActive(Title $title): static
    {
        return self::forModel('titles.errors.pulled.not_active', $title);
    }
}
