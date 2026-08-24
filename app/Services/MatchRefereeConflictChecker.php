<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Collection;

final class MatchRefereeConflictChecker
{
    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Referee>  $referees
     */
    public function ensureCanBeAssigned(int $eventId, Collection $conflictingEventIds, Collection $referees): void
    {
        $conflictingEventIds = $conflictingEventIds
            ->reject(fn (int $conflictingEventId): bool => $conflictingEventId === $eventId);

        if ($conflictingEventIds->isEmpty()) {
            return;
        }

        $conflictingReferee = EventMatch::query()
            ->forEventIds($conflictingEventIds)
            ->withAnyRefereeIds($referees->map(fn (Referee $referee): int => $referee->id))
            ->with('referees:id,first_name,last_name,full_name')
            ->get()
            ->flatMap->referees
            ->first(fn (Referee $referee): bool => $referees->contains('id', $referee->id));

        if ($conflictingReferee === null) {
            return;
        }

        throw SchedulingConflictException::refereeAlreadyAssigned($conflictingReferee->full_name);
    }
}
