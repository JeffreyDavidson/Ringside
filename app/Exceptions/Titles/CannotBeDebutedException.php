<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeDebutedException extends BaseBusinessException
{
    public static function alreadyDebuted(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} has already been debuted and cannot be debuted again.");
    }

    public static function retired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is retired and cannot be debuted.");
    }
}
