<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

class CannotBeReleasedException extends BaseBusinessException
{
    public static function notEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new static("{$context} cannot be released because it is not currently employed. Only employed tag teams can be released from their contracts.");
    }
}
