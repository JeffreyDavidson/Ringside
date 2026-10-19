<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Exceptions\Matches\CannotBeDeletedException;
use App\Models\Matches\EventMatch;
use App\Models\Titles\TitleChampionship;

final class MatchDeletionEligibility
{
    /** A reign points at the match that created or closed it, so the result has to be corrected or cleared before the match can go. */
    public static function ensureCanBeDeleted(EventMatch $match): void
    {
        if (TitleChampionship::query()->wonOrLostAtMatches([$match->id])->exists()) {
            throw CannotBeDeletedException::hasTitleChange();
        }
    }
}
