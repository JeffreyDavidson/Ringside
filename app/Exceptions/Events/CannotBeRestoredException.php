<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Events\Venue;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Venue $venue): static
    {
        $context = self::formatModelContext($venue);

        return self::forReason(BusinessRuleReason::NotDeleted, __('venues.errors.restored.not_deleted', ['context' => $context]));
    }

    public static function nameConflict(Venue $venue, string $conflictingName): static
    {
        $context = self::formatModelContext($venue);

        return new self(__('venues.errors.restored.name_conflict', ['context' => $context, 'conflicting_name' => $conflictingName]));
    }
}
