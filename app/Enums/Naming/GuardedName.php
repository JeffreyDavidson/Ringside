<?php

declare(strict_types=1);

namespace App\Enums\Naming;

/**
 * The record values whose uniqueness within a promotion (a venue name's, across all of them) is guarded by RecordNameLock.
 */
enum GuardedName: string
{
    case TagTeamName = 'tag_team_name';
    case TagTeamSignatureMove = 'tag_team_signature_move';
    case TitleName = 'title_name';
    case WrestlerName = 'wrestler_name';
    case WrestlerSignatureMove = 'wrestler_signature_move';
    case EventName = 'event_name';
    case VenueName = 'venue_name';
}
