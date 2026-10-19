<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Booking;

use Illuminate\Support\Carbon;

interface RosterBookingStrategy
{
    /**
     * Without a date the roster member must be employed now. With one (an event's date) they must
     * have been employed at that moment instead; retirement, suspension and injury are always read as they stand now.
     */
    public function allows(?Carbon $on = null): bool;
}
