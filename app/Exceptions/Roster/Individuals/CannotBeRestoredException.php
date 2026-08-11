<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Model $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::NotDeleted, "{$context} is not deleted and cannot be restored.");
    }
}
