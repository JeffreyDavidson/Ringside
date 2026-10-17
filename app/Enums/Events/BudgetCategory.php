<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum BudgetCategory: string
{
    case Talent = 'talent';
    case Travel = 'travel';
    case Venue = 'venue';
    case Ring = 'ring';
    case Production = 'production';
    case Tickets = 'tickets';
    case Sponsorship = 'sponsorship';
    case Merchandise = 'merchandise';
    case Other = 'other';

    public function label(): string
    {
        return __("event-budget.{$this->value}");
    }

    public function accepts(BudgetDirection $direction): bool
    {
        if ($this === self::Other) {
            return true;
        }

        return in_array($this, $direction === BudgetDirection::Income
            ? [self::Tickets, self::Sponsorship, self::Merchandise]
            : [self::Talent, self::Travel, self::Venue, self::Ring, self::Production], true);
    }
}
