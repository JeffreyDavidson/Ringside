<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeDebutedException extends BaseBusinessException
{
    public static function alreadyDebuted(Title $title): static
    {
        return self::forModel('titles.errors.debuted.already_debuted', $title);
    }
}
