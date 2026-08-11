<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

final class CannotBeRetiredException extends BaseBusinessException
{
    public static function notEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} is not currently employed and cannot be retired.");
    }

    public static function alreadyRetired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} is already retired.");
    }
}
