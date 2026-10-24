<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Title $title): static
    {
        return self::forModel('titles.errors.restored.not_deleted', $title, reason: BusinessRuleReason::NotDeleted);
    }

    public static function nameConflict(Title $title, string $conflictingName): static
    {
        return self::forModel('titles.errors.restored.name_conflict', $title, ['conflicting_name' => $conflictingName]);
    }
}
