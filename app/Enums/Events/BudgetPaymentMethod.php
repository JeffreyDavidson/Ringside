<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum BudgetPaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Other = 'other';
}
