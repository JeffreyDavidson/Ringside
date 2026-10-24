<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeReinstatedException extends BaseBusinessException
{
    public static function active(Title $title): static
    {
        return self::forModel('titles.errors.reinstated.active', $title);
    }

    public static function retired(Title $title): static
    {
        return self::forModel('titles.errors.reinstated.retired', $title);
    }

    public static function neverActivated(Title $title): static
    {
        return self::forModel('titles.errors.reinstated.never_activated', $title);
    }

    public static function scheduledDebut(Title $title): static
    {
        return self::forModel('titles.errors.reinstated.scheduled_debut', $title);
    }
}
