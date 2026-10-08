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

        return self::forReason(BusinessRuleReason::NotDeleted, __('titles.errors.restored.not_deleted', ['context' => $context]));
    }

    public static function nameConflict(Title $title, string $conflictingName): static
    {
        $context = self::formatModelContext($title);

        return new self(__('titles.errors.restored.name_conflict', ['context' => $context, 'conflicting_name' => $conflictingName]));
    }
}
