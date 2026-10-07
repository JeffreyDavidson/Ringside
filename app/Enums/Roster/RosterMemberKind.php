<?php

declare(strict_types=1);

namespace App\Enums\Roster;

/** The roster record types the tag team and stable forms offer through their searchable selects. */
enum RosterMemberKind: string
{
    case Wrestlers = 'wrestlers';
    case TagTeams = 'tag_teams';
    case Managers = 'managers';
}
