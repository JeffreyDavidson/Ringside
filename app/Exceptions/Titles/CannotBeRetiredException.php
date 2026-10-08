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

        return new self(__('titles.errors.retired.unactivated', ['context' => $context]));
    }

    public static function hasFutureDebut(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.retired.has_future_debut', ['context' => $context]));
    }

    public static function alreadyRetired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.retired.already_retired', ['context' => $context]));
    }
}
