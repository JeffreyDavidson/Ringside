<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeUnretiredException extends BaseBusinessException
{
    public static function deleted(Title $title): static
    {
        return self::forModel('titles.errors.unretired.deleted', $title);
    }

    public static function notRetired(Title $title): static
    {
        return self::forModel('titles.errors.unretired.not_retired', $title);
    }
}
