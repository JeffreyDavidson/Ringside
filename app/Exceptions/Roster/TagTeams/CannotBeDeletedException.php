<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

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

        return new static("{$context} cannot be deleted because it is retired. Unretire the tag team before deletion.");
    }

    public static function stillEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new static("{$context} cannot be deleted because it is still employed. Release the tag team from employment before deletion.");
    }

    public static function stillSuspended(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new static("{$context} cannot be deleted because it is currently suspended. Resolve suspension status before deletion.");
    }
}
