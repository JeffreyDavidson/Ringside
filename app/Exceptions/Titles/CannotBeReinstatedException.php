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

        return new self(__('titles.errors.reinstated.active', ['context' => $context]));
    }

    public static function retired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.reinstated.retired', ['context' => $context]));
    }

    public static function neverActivated(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.reinstated.never_activated', ['context' => $context]));
    }

    public static function scheduledDebut(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.reinstated.scheduled_debut', ['context' => $context]));
    }
}
