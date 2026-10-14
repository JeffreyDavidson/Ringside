<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum BudgetMovementKind: string
{
    case Payment = 'payment';
    case Refund = 'refund';
}
