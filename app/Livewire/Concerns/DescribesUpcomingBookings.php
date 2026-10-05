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
    private const int BOOKING_SUMMARY_LIMIT = 5;

    /**
     * The upcoming or unresulted events a roster member is booked in (the first five, then a count of the rest) as a
     * comma-separated list, or an empty string.
     */
    protected function upcomingBookingSummary(Wrestler|Referee|TagTeam $rosterMember): string
    {
        $events = app(UpcomingBookings::class)->events($rosterMember);

        $summary = $events
            ->take(self::BOOKING_SUMMARY_LIMIT)
            ->map(fn (Event $event): string => sprintf(
                '%s (%s)',
                $event->name,
                $event->local_date?->format('M j, Y') ?? __('core.lifecycle_confirmations.booked_unscheduled'),
            ))
            ->implode(', ');

        $remaining = $events->count() - self::BOOKING_SUMMARY_LIMIT;

        if ($remaining <= 0) {
            return $summary;
        }

        return "{$summary} ".__('core.lifecycle_confirmations.booked_and_more', ['count' => $remaining]);
    }
}
