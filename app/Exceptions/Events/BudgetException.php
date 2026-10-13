<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;

final class BudgetException extends BaseBusinessException
{
    public static function because(string $key): static
    {
        return new self(__('event-budget.'.$key));
    }
}
