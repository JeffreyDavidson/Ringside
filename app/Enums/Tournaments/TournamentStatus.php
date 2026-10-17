<?php

declare(strict_types=1);

namespace App\Enums\Tournaments;

enum TournamentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("tournaments.status.{$this->value}");
    }
}
