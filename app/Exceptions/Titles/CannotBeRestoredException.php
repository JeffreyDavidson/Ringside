<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Title $title): static
    {
        $context = self::formatModelContext($title);

        return self::forReason(BusinessRuleReason::NotDeleted, "{$context} cannot be restored because it is not deleted.");
    }

    public static function nameConflict(Title $title, string $conflictingName): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be restored because the name conflicts with existing title '{$conflictingName}'. Resolve the conflict before restoration.");
    }
}
