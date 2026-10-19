<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Data\Stables\StableData;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeUpdatedException;
use App\Lifecycle\Roster\Stables\StableActivityEligibility;
use App\Lifecycle\Roster\Stables\StableNameLock;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /**
     * Create a new update action instance.
     */
    public function __construct(
        protected EstablishAction $establishAction,
        protected SynchronizeStableMembersAction $synchronizeStableMembersAction,
        protected StableActivityEligibility $eligibility,
        protected StableNameLock $nameLock,
    ) {}

    /**
     * Update a stable.
     *
     * This handles the complete stable update workflow:
     * - Updates the stable name, rejecting one another active stable of the promotion already uses; a stable without a
     *   promotion has no database-level name guard on MySQL, so it first takes the name lock, before its own row lock
     * - Establishes a stable that has no activity history when a start date is given
     * - Moves the dates of a disbanded stable's only activity period
     * - Updates stable membership (wrestlers, tag teams)
     *
     * Disbanding, reuniting and changing earlier periods never happen here: an end date cannot close
     * an open period (DisbandAction owns that), a blank end date never reopens a closed one
     * (ReuniteAction owns that), and once a stable has several periods its start date is fixed.
     *
     * @param  Stable  $stable  The stable to update
     * @param  StableData  $stableData  The updated stable information
     * @return Stable The updated stable instance
     *
     * @throws CannotBeUpdatedException When the data would end an open period, move a locked start date, give a disbanded stable members, or use a name another active stable has
     * @throws CannotBeEstablishedException When an end date is given while establishing the stable
     */
    public function handle(Stable $stable, StableData $stableData): Stable
    {
        if ($stableData->start_date instanceof Carbon && $stableData->end_date instanceof Carbon && $stableData->end_date->lt($stableData->start_date)) {
            throw InvalidDateRangeException::endBeforeStart(
                $stableData->start_date,
                $stableData->end_date,
                'stable activity',
            );
        }

        return DB::transaction(function () use ($stable, $stableData): Stable {
            $name = mb_trim($stableData->name);

            if ($stable->promotion_id === null) {
                $this->nameLock->lock($name);
            }

            $lockedStable = $stable->refreshForUpdate();

            if ($stableData->members->isNotEmpty() && ! $this->eligibility->canHaveMembers($lockedStable)) {
                throw CannotBeUpdatedException::inactiveWithMembers($lockedStable);
            }

            $nameTaken = Stable::query()
                ->withoutGlobalScope(PromotionContextScope::class)
                ->whereNameInPromotion($name, $lockedStable->promotion_id)
                ->whereKeyNot($lockedStable->getKey())
                ->exists();

            if ($nameTaken) {
                throw CannotBeUpdatedException::nameTaken($name);
            }

            try {
                $lockedStable->update([
                    'name' => $name,
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent request took the name of this promotion after the check above; the unique index refused the write.
                throw CannotBeUpdatedException::nameTaken($name);
            }

            $this->synchronizeStableMembersAction->handle($lockedStable, $stableData->members, now());

            if ($stableData->start_date instanceof Carbon) {
                $this->updateActivity($lockedStable, $stableData->start_date, $stableData->end_date);
            }

            return $lockedStable;
        });
    }

    private function updateActivity(Stable $stable, Carbon $startDate, ?Carbon $endDate): void
    {
        $periods = $stable->activityPeriods()
            ->orderBy('started_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $firstPeriod = $periods->first();

        if (! $firstPeriod instanceof ActivityPeriod) {
            if ($endDate instanceof Carbon) {
                throw CannotBeEstablishedException::withEndDate($stable);
            }

            $this->establishAction->handle($stable, $startDate);

            return;
        }

        if ($periods->count() > 1) {
            if (! $firstPeriod->started_at->isSameDay($startDate)) {
                throw CannotBeUpdatedException::startDateLocked($stable);
            }

            return;
        }

        if ($firstPeriod->ended_at === null && $endDate instanceof Carbon) {
            throw CannotBeUpdatedException::endsOpenPeriod($stable);
        }

        $endedAt = $firstPeriod->ended_at === null
            ? null
            : ($endDate ?? $firstPeriod->ended_at);

        if ($endedAt instanceof Carbon && $endedAt->lt($startDate)) {
            throw InvalidDateRangeException::endBeforeStart($startDate, $endedAt, 'stable activity');
        }

        $this->ensureMembershipsStayWithinPeriod($stable, $firstPeriod, $startDate, $endedAt);

        $firstPeriod->update([
            'started_at' => $startDate,
            'ended_at' => $endedAt,
        ]);
    }

    /**
     * Refuse to move a period edge past a membership, because membership history is never rewritten silently.
     * Only an edge that actually moves is checked, so a stable whose history already predates its period can still be saved.
     */
    private function ensureMembershipsStayWithinPeriod(Stable $stable, ActivityPeriod $period, Carbon $startDate, ?Carbon $endedAt): void
    {
        $memberships = $stable->wrestlers()->get()
            ->concat($stable->tagTeams()->get())
            ->pluck('pivot');

        $startMoves = ! $period->started_at->isSameDay($startDate);
        $endMoves = $endedAt instanceof Carbon && $period->ended_at instanceof Carbon && ! $period->ended_at->isSameDay($endedAt);

        $startsTooLate = $startMoves && $memberships->contains(
            fn (Pivot $membership): bool => $membership->joined_at->copy()->startOfDay()->lt($startDate->copy()->startOfDay()),
        );
        $endsTooEarly = $endMoves && $memberships->contains(
            fn (Pivot $membership): bool => $membership->left_at !== null
                && $membership->left_at->copy()->startOfDay()->gt($endedAt->copy()->startOfDay()),
        );

        if ($startsTooLate) {
            throw CannotBeUpdatedException::startsAfterMembership($stable);
        }

        if ($endsTooEarly) {
            throw CannotBeUpdatedException::endsBeforeMembership($stable);
        }
    }
}
