<?php

declare(strict_types=1);

namespace App\Enums\Events;

enum TalentBookingStatus: string
{
    case Proposed = 'proposed';
    case Contacted = 'contacted';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
