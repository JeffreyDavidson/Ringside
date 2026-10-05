<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Lifecycle\Roster\UpcomingBookings;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

trait DescribesUpcomingBookings
{
    /**
     * The upcoming events a roster member is booked in as a comma-separated list, or an empty string.
     */
    protected function upcomingBookingSummary(Wrestler|Referee|TagTeam $rosterMember): string
    {
        return app(UpcomingBookings::class)
            ->events($rosterMember)
            ->map(fn (Event $event): string => "{$event->name} ({$event->date?->format('M j, Y')})")
            ->implode(', ');
    }
}
