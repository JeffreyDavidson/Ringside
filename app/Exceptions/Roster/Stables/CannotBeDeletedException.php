<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function alreadyDeleted(Stable $stable): static
    {
        return self::forModel('stables.errors.deleted.already_deleted', $stable);
    }

    public static function currentlyActive(Stable $stable): static
    {
        return self::forModel('stables.errors.deleted.currently_active', $stable);
    }

    public static function futureEstablishmentScheduled(Stable $stable): static
    {
        return self::forModel('stables.errors.deleted.future_establishment_scheduled', $stable);
    }

    public static function hasCurrentMembers(Stable $stable, int $memberCount): static
    {
        return self::forModel('stables.errors.deleted.has_current_members', $stable, count: $memberCount);
    }
}
