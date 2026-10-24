<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Stable $stable): static
    {
        return self::forModel('stables.errors.restored.not_deleted', $stable);
    }

    public static function nameConflict(Stable $stable, string $conflictingStableName): static
    {
        return self::forModel('stables.errors.restored.name_conflict', $stable, ['conflicting_stable_name' => $conflictingStableName]);
    }
}
