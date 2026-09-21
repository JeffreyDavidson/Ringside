<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Booking;

interface RosterBookingStrategy
{
    public function allows(): bool;
}
