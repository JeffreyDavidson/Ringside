<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new static("{$context} cannot be restored because it is not deleted. Only soft-deleted tag teams can be restored to active status.");
    }

    public static function nameConflict(TagTeam $tagTeam, string $conflictingName): static
    {
        $context = self::formatModelContext($tagTeam);

        return new static("{$context} cannot be restored because the name conflicts with existing active tag team '{$conflictingName}'. Resolve name conflicts before restoration.");
    }
}
