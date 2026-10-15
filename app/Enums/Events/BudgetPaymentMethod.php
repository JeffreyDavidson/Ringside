<?php

declare(strict_types=1);

namespace App\Enums\Events;

use App\Enums\Concerns\ProvidesFilterOptions;

enum BudgetPaymentMethod: string
{
    use ProvidesFilterOptions;

    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';

    public function label(): string
    {
        return __("event-budget.payment_methods.{$this->value}");
    }
}
