<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.restore.not_deleted', $tagTeam, reason: BusinessRuleReason::NotDeleted);
    }

    public static function nameConflict(TagTeam $tagTeam, string $conflictingName): static
    {
        return self::forModel('tag-team-lifecycle.errors.restore.name_conflict', $tagTeam, ['name' => $conflictingName]);
    }
}
