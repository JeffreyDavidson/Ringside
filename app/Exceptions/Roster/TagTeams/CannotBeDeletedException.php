<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function alreadyDeleted(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted because it is already deleted.");
    }

    public static function stillRetired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted because it is retired. Unretire the tag team before deletion.");
    }

    public static function stillEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted because it is still employed. Release the tag team from employment before deletion.");
    }

    public static function bookedInUpcomingMatch(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(
            BusinessRuleReason::BookedInMatch,
            __('core.delete_rejections.booked_in_match', ['context' => $context]),
        );
    }
}
