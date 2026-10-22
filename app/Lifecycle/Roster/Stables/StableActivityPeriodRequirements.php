<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Stables;

use App\Enums\Lifecycle\DateRangeContext;
use App\Exceptions\Lifecycle\InvalidDateRangeException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeUpdatedException;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What an edit of a stable's activity dates may do to the stable's activity periods. It refuses and never writes:
 * UpdateAction locks the periods, calls this class, then establishes the stable or moves the period.
 */
final readonly class StableActivityPeriodRequirements
{
    /**
     * @param  Collection<int, ActivityPeriod>  $periods  The stable's activity periods, oldest first, locked by the caller
     *
     * @throws CannotBeEstablishedException When an end date is given while establishing the stable
     * @throws CannotBeUpdatedException When the dates would end an open period, move a locked start date, or move a period edge past a membership
     * @throws InvalidDateRangeException When the moved period would end before it starts
     */
    public function ensureCanMove(Stable $stable, Collection $periods, Carbon $startDate, ?Carbon $endDate): void
    {
        $firstPeriod = $periods->first();

        if (! $firstPeriod instanceof ActivityPeriod) {
            if ($endDate instanceof Carbon) {
                throw CannotBeEstablishedException::withEndDate($stable);
            }

            return;
        }

        if ($periods->count() > 1) {
            if (! $this->isSamePromotionDay($stable, $firstPeriod->started_at, $startDate)) {
                throw CannotBeUpdatedException::startDateLocked($stable);
            }

            return;
        }

        if ($firstPeriod->ended_at === null && $endDate instanceof Carbon) {
            throw CannotBeUpdatedException::endsOpenPeriod($stable);
        }

        $endedAt = $this->endAfterMove($firstPeriod, $endDate);

        if ($endedAt instanceof Carbon && $endedAt->lt($startDate)) {
            throw InvalidDateRangeException::endBeforeStart($startDate, $endedAt, DateRangeContext::StableActivity);
        }

        $this->ensureMembershipsStayWithinPeriod($stable, $firstPeriod, $startDate, $endedAt);
    }

    /**
     * The end a stable's only period has once an edit is applied: an open period stays open, and a closed one keeps
     * its end unless a new end date is given.
     */
    public function endAfterMove(ActivityPeriod $period, ?Carbon $endDate): ?Carbon
    {
        if ($period->ended_at === null) {
            return null;
        }

        return $endDate ?? $period->ended_at;
    }

    /**
     * Refuse to move a period edge past a membership, because membership history is never rewritten silently.
     * Only an edge that actually moves is checked, so a stable whose history already predates its period can still be saved.
     */
    private function ensureMembershipsStayWithinPeriod(Stable $stable, ActivityPeriod $period, Carbon $startDate, ?Carbon $endedAt): void
    {
        $local = fn (Carbon $date): Carbon => PromotionTimeZoneService::toLocalTime($stable->promotion, $date);

        $startMoves = ! $this->isSamePromotionDay($stable, $period->started_at, $startDate);
        $endMoves = $endedAt instanceof Carbon && $period->ended_at instanceof Carbon
            && ! $this->isSamePromotionDay($stable, $period->ended_at, $endedAt);

        $startsTooLate = false;
        if ($startMoves) {
            $firstLocalMoment = $local($startDate)->startOfDay()->utc();
            $startsTooLate = $stable->wrestlers()->wherePivot('joined_at', '<', $firstLocalMoment)->exists()
                || $stable->tagTeams()->wherePivot('joined_at', '<', $firstLocalMoment)->exists();
        }

        $endsTooEarly = false;
        if ($endMoves) {
            $lastLocalMoment = $local($endedAt)->endOfDay()->utc();
            $endsTooEarly = $stable->wrestlers()->wherePivot('left_at', '>', $lastLocalMoment)->exists()
                || $stable->tagTeams()->wherePivot('left_at', '>', $lastLocalMoment)->exists();
        }

        if ($startsTooLate) {
            throw CannotBeUpdatedException::startsAfterMembership($stable);
        }

        if ($endsTooEarly) {
            throw CannotBeUpdatedException::endsBeforeMembership($stable);
        }
    }

    /**
     * Whether two instants fall on the same day in the stable's promotion time zone (the application time zone without
     * a promotion), which is how the form reads and compares them.
     */
    private function isSamePromotionDay(Stable $stable, Carbon $first, Carbon $second): bool
    {
        return PromotionTimeZoneService::toLocalTime($stable->promotion, $first)
            ->isSameDay(PromotionTimeZoneService::toLocalTime($stable->promotion, $second));
    }
}
