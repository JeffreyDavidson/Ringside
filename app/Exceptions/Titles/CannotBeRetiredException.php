<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeRetiredException extends BaseBusinessException
{
    public static function unactivated(Title $title): static
    {
        return self::forModel('titles.errors.retired.unactivated', $title);
    }

    public static function hasFutureDebut(Title $title): static
    {
        return self::forModel('titles.errors.retired.has_future_debut', $title);
    }

    public static function alreadyRetired(Title $title): static
    {
        return self::forModel('titles.errors.retired.already_retired', $title);
    }
}
