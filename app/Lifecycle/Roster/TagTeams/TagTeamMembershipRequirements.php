<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\TagTeams;

use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

final class TagTeamMembershipRequirements
{
    public const int MINIMUM_CURRENT_WRESTLERS = 2;

    /** @param Collection<int, Wrestler> $wrestlers */
    public static function hasMinimumCurrentWrestlers(Collection $wrestlers): bool
    {
        return $wrestlers->count() >= self::MINIMUM_CURRENT_WRESTLERS;
    }
}
