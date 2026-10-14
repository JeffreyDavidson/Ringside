<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum BudgetDirection: string
{
    case Income = 'income';
    case Expense = 'expense';
}
