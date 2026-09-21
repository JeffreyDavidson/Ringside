<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not deleted and cannot be restored.");
    }

    public static function nameConflict(Stable $stable, string $conflictingStableName): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be restored: name conflicts with existing stable '{$conflictingStableName}'.");
    }
}
