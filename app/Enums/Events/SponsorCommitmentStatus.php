<?php

declare(strict_types=1);

namespace App\Enums\Events;

use App\Enums\Concerns\ProvidesFilterOptions;

enum SponsorCommitmentStatus: string
{
    use ProvidesFilterOptions;

    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('sponsors.'.$this->value);
    }
}
