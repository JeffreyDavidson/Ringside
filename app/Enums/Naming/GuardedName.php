<?php

declare(strict_types=1);

namespace App\Enums\Naming;

/**
 * The record values whose uniqueness within a promotion is guarded by RecordNameLock.
 */
enum GuardedName: string
{
    case TagTeamName = 'tag_team_name';
    case TagTeamSignatureMove = 'tag_team_signature_move';
    case TitleName = 'title_name';
}
