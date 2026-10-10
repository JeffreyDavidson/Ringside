<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;

final class NameTakenException extends BaseBusinessException
{
    public static function name(string $name): static
    {
        return self::forReason(BusinessRuleReason::NameTaken, __('titles.validation.name_taken', ['name' => $name]));
    }
}
