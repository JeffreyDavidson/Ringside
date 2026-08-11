<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeReinstatedException extends BaseBusinessException
{
    public static function active(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} is already active and cannot be reinstated.");
    }

    public static function retired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is retired and cannot be reinstated.");
    }

    public static function neverActivated(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} has never been activated and cannot be reinstated. Use debut workflow instead.");
    }
}
