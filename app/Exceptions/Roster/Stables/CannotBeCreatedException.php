<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;

final class CannotBeCreatedException extends BaseBusinessException
{
    public static function nameTaken(string $name): static
    {
        return self::forReason(BusinessRuleReason::NameTaken, __('stables.errors.created.name_taken', ['name' => $name]));
    }
}
