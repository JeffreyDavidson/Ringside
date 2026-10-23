<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster;

use App\Exceptions\Scheduling\EntityNotAvailableException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\EventRosterService;

final class RosterDateAvailability
{
    public function allowsOn(Wrestler|Referee|TagTeam $person, ?string $date): bool
    {
        if ($date === null) {
            return true;
        }

        if ($person instanceof TagTeam) {
            $person->loadMissing('currentWrestlers');

            return $person->currentWrestlers->every(fn (Wrestler $member): bool => $this->allowsOn($member, $date));
        }

        return ! Unavailability::query()->whereMorphedTo('person', $person)->where('promotion_id', $person->promotion_id)->covering($date)->exists();
    }

    public function allowsForEvent(Wrestler|Referee|TagTeam $person, Event $event): bool
    {
        $event->loadMissing('promotion');

        return $this->allowsOn($person, $event->local_date?->toDateString());
    }

    public function ensureForEvent(Wrestler|Referee|TagTeam $person, Event $event): void
    {
        $event->loadMissing('promotion');
        $date = $event->local_date;

        if ($date !== null && ! $this->allowsOn($person, $date->toDateString())) {
            throw EntityNotAvailableException::onDate($person instanceof Referee ? $person->full_name : $person->name, $date->toDateString());
        }
    }

    /** The `type:id` key is owned by EventRosterService::key(); this keeps the Lifecycle callers on one format. */
    public static function key(Wrestler|Referee|TagTeam $person): string
    {
        return EventRosterService::key($person);
    }

    /** Only persisted assignments on this match may retain a calendar restriction. */
    public function retained(Wrestler|Referee|TagTeam $person, Event $event, ?EventMatch $match): bool
    {
        if (! $match instanceof EventMatch || $match->event_id !== $event->id) {
            return false;
        }

        if ($person instanceof Referee) {
            return $match->referees()->whereKey($person->id)->exists();
        }

        return $match->competitors()->where('competitor_type', $person->getMorphClass())->where('competitor_id', $person->id)->exists();
    }
}
