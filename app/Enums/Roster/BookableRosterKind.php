<?php

declare(strict_types=1);

namespace App\Enums\Roster;

/** The roster record types the match form offers through its searchable selects. */
enum BookableRosterKind: string
{
    case Wrestlers = 'wrestlers';
    case TagTeams = 'tag_teams';
    case Referees = 'referees';
}
