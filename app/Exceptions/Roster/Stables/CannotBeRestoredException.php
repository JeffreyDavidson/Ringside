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

        return new self(__('stables.errors.restored.not_deleted', ['context' => $context]));
    }

    public static function nameConflict(Stable $stable, string $conflictingStableName): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.restored.name_conflict', ['context' => $context, 'conflicting_stable_name' => $conflictingStableName]));
    }
}
