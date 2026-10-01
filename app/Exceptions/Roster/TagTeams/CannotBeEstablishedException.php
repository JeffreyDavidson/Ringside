<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeEstablishedException extends BaseBusinessException
{
    public static function wrestlerOnAnotherTagTeam(Wrestler $wrestler, TagTeam $currentTagTeam): static
    {
        $wrestlerContext = self::formatModelContext($wrestler);
        $tagTeamContext = self::formatModelContext($currentTagTeam);

        return new self("{$wrestlerContext} is already a member of {$tagTeamContext} and cannot join another tag team.");
    }
}
