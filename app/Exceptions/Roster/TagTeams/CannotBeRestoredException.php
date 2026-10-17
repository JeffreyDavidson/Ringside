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
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::NotDeleted, __('tag-team-lifecycle.errors.restore.not_deleted', ['context' => $context]));
    }

    public static function nameConflict(TagTeam $tagTeam, string $conflictingName): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(__('tag-team-lifecycle.errors.restore.name_conflict', ['context' => $context, 'name' => $conflictingName]));
    }
}
