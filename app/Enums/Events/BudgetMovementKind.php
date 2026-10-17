<?php

declare(strict_types=1);

namespace App\Enums\Events;

use App\Enums\Concerns\ProvidesFilterOptions;

enum BudgetMovementKind: string
{
    use ProvidesFilterOptions;

    case Payment = 'payment';
    case Refund = 'refund';

    public function label(): string
    {
        return __("event-budget.{$this->value}");
    }
}
