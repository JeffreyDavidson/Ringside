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

        return self::forReason(BusinessRuleReason::NotDeleted, "{$context} cannot be restored because it is not deleted.");
    }

    public static function nameConflict(Venue $venue, string $conflictingName): static
    {
        $context = self::formatModelContext($venue);

        return new static("{$context} cannot be restored because the name conflicts with existing venue '{$conflictingName}'. Resolve the conflict before restoration.");
    }
}
