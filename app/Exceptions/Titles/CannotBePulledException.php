<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBePulledException extends BaseBusinessException
{
    public static function notActive(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} is not currently active and cannot be pulled from competition.");
    }

    public static function retired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is retired and cannot be pulled from competition.");
    }
}
