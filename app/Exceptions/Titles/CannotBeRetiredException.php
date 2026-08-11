<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeRetiredException extends BaseBusinessException
{
    public static function unactivated(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} has never been activated and cannot be retired.");
    }

    public static function hasFutureDebut(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} has future debut scheduled and cannot be retired before activation.");
    }

    public static function alreadyRetired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} is already retired and cannot be retired again.");
    }
}
