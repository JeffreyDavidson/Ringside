<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeUnretiredException extends BaseBusinessException
{
    public static function deleted(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.unretired.deleted', ['context' => $context]));
    }

    public static function notRetired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.unretired.not_retired', ['context' => $context]));
    }
}
