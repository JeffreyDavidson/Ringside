<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeUnretiredException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        return self::forModel('stables.errors.unretired.deleted', $stable);
    }

    public static function notRetired(Stable $stable): static
    {
        return self::forModel('stables.errors.unretired.not_retired', $stable);
    }

    public static function insufficientFormerMembers(Stable $stable, int $availableCount, int $minimumRequired): static
    {
        return self::forModel('stables.errors.unretired.insufficient_former_members', $stable, ['available_count' => $availableCount, 'minimum_required' => $minimumRequired]);
    }
}
