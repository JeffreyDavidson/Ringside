<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function alreadyDeleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.deleted.already_deleted', ['context' => $context]));
    }

    public static function currentlyActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.deleted.currently_active', ['context' => $context]));
    }

    public static function futureEstablishmentScheduled(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.deleted.future_establishment_scheduled', ['context' => $context]));
    }

    public static function hasCurrentMembers(Stable $stable, int $memberCount): static
    {
        $context = self::formatModelContext($stable);

        return new self(trans_choice('stables.errors.deleted.has_current_members', $memberCount, ['context' => $context]));
    }
}
