<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum SponsorCommitmentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('sponsors.'.$this->value);
    }
}
