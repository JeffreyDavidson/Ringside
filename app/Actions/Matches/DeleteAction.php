<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Lifecycle\Matches\MatchDeletionEligibility;
use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Tournaments\TournamentMatchRequirements;
use App\Models\Matches\EventMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(private readonly DeletionStateManager $deletionState, private readonly MatchSchedulingLock $schedulingLock, private readonly TournamentMatchRequirements $tournamentRequirements) {}

    public function handle(EventMatch $eventMatch, ?Carbon $deletedAt = null): void
    {
        DB::transaction(function () use ($eventMatch, $deletedAt): void {
            $lockedMatch = $this->schedulingLock->lockMatchWithEventSet($eventMatch);
            $this->tournamentRequirements->ensureDeletable($lockedMatch);
            MatchDeletionEligibility::ensureCanBeDeleted($lockedMatch);

            $this->deletionState->delete($lockedMatch, $deletedAt ?? now());
        });
    }
}
