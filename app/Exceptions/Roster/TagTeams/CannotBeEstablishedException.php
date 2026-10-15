<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeEstablishedException extends BaseBusinessException
{
    public static function wrestlerOnAnotherTagTeam(Wrestler $wrestler, TagTeam $currentTagTeam): static
    {
        return self::forReason(BusinessRuleReason::PartnerOnAnotherTagTeam, __('tag-teams.validation.wrestler_on_another_tag_team', [
            'wrestler' => $wrestler->name,
            'tag_team' => $currentTagTeam->name,
        ]));
    }
}
