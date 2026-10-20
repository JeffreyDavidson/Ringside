<?php

declare(strict_types=1);

namespace App\Lifecycle\Titles;

use App\Collections\MatchCompetitorsCollection;
use App\Collections\TitleChampionshipCollection;
use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Contracts\CanBeChampion;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Retirement;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class ChampionshipReignManager
{
    /**
     * Booking only checks the champion at booking time, so recording re-checks that whoever held the title on the
     * event date is competing: a result can never take the title off a champion who was not in the match. A title
     * that was vacant on the event date has no champion to check, and a reign won at the same instant by another
     * match is ignored because the order of two matches at one instant is not recorded.
     *
     * @param  MatchCompetitorsCollection<int, MatchCompetitor>  $competitors
     * @param  TitleChampionshipCollection<int, TitleChampionship>  $reigns
     */
    public function ensureChampionOnEventDateIsCompeting(
        EventMatch $match,
        Title $title,
        MatchCompetitorsCollection $competitors,
        TitleChampionshipCollection $reigns,
    ): void {
        $eventDate = $match->event->date;

        if (! $eventDate instanceof Carbon) {
            return;
        }

        $reignHeldOnEventDate = $reigns->forTitle($title)->heldBefore($eventDate, $match);

        if ($reignHeldOnEventDate instanceof TitleChampionship
            && ! $competitors->containsCompetitor($reignHeldOnEventDate->champion_type, $reignHeldOnEventDate->champion_id)) {
            throw InvalidMatchOutcomeException::titleChampionNotInMatch($title);
        }
    }

    /** @param TitleChampionshipCollection<int, TitleChampionship> $reigns */
    public function ensureMatchCanBeReconciled(
        EventMatch $match,
        Title $title,
        Wrestler|TagTeam|null $desiredChampion,
        TitleChampionshipCollection $reigns,
    ): void {
        $activeReigns = $reigns->forTitle($title);
        $reignWonAtMatch = $activeReigns->wonAt($match);

        if ($reignWonAtMatch?->lost_match_id !== null) {
            throw InvalidMatchOutcomeException::titleLineageHasAdvanced();
        }

        if ($this->reignBelongsTo($reignWonAtMatch, $desiredChampion)) {
            return;
        }

        $eventDate = $match->event->date;

        if (! $eventDate instanceof Carbon || (! $reignWonAtMatch instanceof TitleChampionship && $desiredChampion === null)) {
            return;
        }

        if ($this->isDefenceOfReignHeldOnEventDate($match, $reignWonAtMatch, $desiredChampion, $activeReigns)) {
            return;
        }

        $laterReignExists = $activeReigns->laterThan($eventDate, $match)->isNotEmpty();

        $vacatedReignContainsDate = $desiredChampion !== null && $activeReigns->contains(
            fn (TitleChampionship $reign): bool => $reign->won_match_id !== $match->id
                && $reign->lost_at !== null
                && $reign->won_at->lessThanOrEqualTo($eventDate)
                && $reign->lost_at->greaterThan($eventDate),
        );

        if ($laterReignExists || $vacatedReignContainsDate) {
            throw InvalidMatchOutcomeException::titleResultOutOfDateOrder($title);
        }
    }

    /**
     * Determine whether recording the desired champion would put someone other than the current holder on the title.
     *
     * @param  TitleChampionshipCollection<int, TitleChampionship>  $reigns
     */
    public function changesChampion(
        EventMatch $match,
        Title $title,
        Wrestler|TagTeam|null $desiredChampion,
        TitleChampionshipCollection $reigns,
    ): bool {
        if ($desiredChampion === null) {
            return false;
        }

        $activeReigns = $reigns->forTitle($title);
        $reignWonAtMatch = $activeReigns->wonAt($match);

        if ($reignWonAtMatch instanceof TitleChampionship) {
            return ! $this->reignBelongsTo($reignWonAtMatch, $desiredChampion);
        }

        if ($this->isDefenceOfReignHeldOnEventDate($match, null, $desiredChampion, $activeReigns)) {
            return false;
        }

        return ! $this->reignBelongsTo(
            $activeReigns->current(),
            $desiredChampion,
        );
    }

    /** @param TitleChampionshipCollection<int, TitleChampionship> $reigns */
    public function reconcileMatchOutcome(
        EventMatch $match,
        Title $title,
        Wrestler|TagTeam|null $desiredChampion,
        TitleChampionshipCollection $reigns,
    ): void {
        $activeReigns = $reigns->forTitle($title);
        $reignWonAtMatch = $activeReigns->wonAt($match);
        $currentReign = $activeReigns->current();

        if ($reignWonAtMatch instanceof TitleChampionship && $this->reignBelongsTo($reignWonAtMatch, $desiredChampion)) {
            return;
        }

        if ($this->isDefenceOfReignHeldOnEventDate($match, $reignWonAtMatch, $desiredChampion, $activeReigns)) {
            return;
        }

        if ($reignWonAtMatch instanceof TitleChampionship) {
            $reignWonAtMatch->delete();

            $predecessor = $activeReigns->lostAt($match);
            $currentReign = $this->reopenablePredecessor($title, $predecessor);
            $currentReign?->update([
                'lost_match_id' => null,
                'lost_at' => null,
            ]);

            if (! $currentReign instanceof TitleChampionship && $this->reopenDepartedChampionsReign($match, $title, $predecessor, $desiredChampion)) {
                return;
            }
        }

        if ($desiredChampion === null || $this->reignBelongsTo($currentReign, $desiredChampion)) {
            return;
        }

        $eventDate = $match->event->date;

        if (! $eventDate instanceof Carbon) {
            throw InvalidMatchOutcomeException::undatedTitleMatch();
        }

        $currentReign?->update([
            'lost_match_id' => $match->id,
            'lost_at' => $eventDate,
        ]);

        $newReign = TitleChampionship::query()->create([
            'title_id' => $title->id,
            'champion_type' => $desiredChampion->getMorphClass(),
            'champion_id' => $desiredChampion->id,
            'won_match_id' => $match->id,
            'won_at' => $eventDate,
        ]);

        if ($this->isBackFill($match)) {
            $this->endReignIfChampionHasSinceLeft($newReign, $match, $desiredChampion);
        }
    }

    /**
     * A correction back to the champion this match dethroned reopens that champion's original reign even though they
     * have since been released or retired, ending it on their release or retirement date so it stays one continuous
     * reign. Without a recorded departure, or on a title that is no longer active, nothing is reopened.
     */
    private function reopenDepartedChampionsReign(
        EventMatch $match,
        Title $title,
        ?TitleChampionship $predecessor,
        Wrestler|TagTeam|null $desiredChampion,
    ): bool {
        $eventDate = $match->event->date;

        if (! $predecessor instanceof TitleChampionship
            || ! $desiredChampion instanceof Model
            || ! $eventDate instanceof Carbon
            || ! $this->reignBelongsTo($predecessor, $desiredChampion)
            || ! $title->currentActivityPeriod()->exists()) {
            return false;
        }

        $leftAt = $this->firstLeaveOnOrAfterEventDay($match, $eventDate, $desiredChampion);

        if (! $leftAt instanceof Carbon) {
            return false;
        }

        $predecessor->update([
            'lost_match_id' => null,
            'lost_at' => $this->reignEnd($predecessor, $leftAt),
        ]);

        return true;
    }

    /** An event from an earlier day in its promotion's time zone is being back-filled rather than recorded live. */
    public function isBackFill(EventMatch $match): bool
    {
        $event = $match->event;

        return $event->local_date?->isBefore(
            PromotionTimeZoneService::toLocalTime($event->promotion, now())->startOfDay(),
        ) ?? false;
    }

    /**
     * A back-filled win can be recorded after its winner was released or retired, when the release found no reign to
     * close. The win stays in the title history but the reign ends where a release or retirement while champion
     * would have ended it: on the first release or retirement day (in the promotion's time zone) on or after the
     * event day, even if the winner has since been re-employed. A winner who never left keeps an open reign.
     */
    private function endReignIfChampionHasSinceLeft(TitleChampionship $reign, EventMatch $match, Wrestler|TagTeam $champion): void
    {
        $leftAt = $this->firstLeaveOnOrAfterEventDay($match, $reign->won_at, $champion);

        if (! $leftAt instanceof Carbon) {
            return;
        }

        $reign->update(['lost_at' => $this->reignEnd($reign, $leftAt)]);
    }

    /**
     * The earliest release or retirement of the champion that falls on or after the event's local day in the
     * promotion's time zone, ignoring earlier departures and later returns.
     */
    private function firstLeaveOnOrAfterEventDay(EventMatch $match, Carbon $eventDate, Wrestler|TagTeam $champion): ?Carbon
    {
        $promotion = $match->event->promotion;
        $eventDay = PromotionTimeZoneService::toLocalTime($promotion, $eventDate)->startOfDay();

        return $champion->employments()->whereNotNull('ended_at')->get()
            ->map(fn (Employment $employment): ?Carbon => $employment->ended_at)
            ->merge($champion->retirements()->get()->map(fn (Retirement $retirement): Carbon => $retirement->started_at))
            ->filter(fn (?Carbon $date): bool => $date instanceof Carbon
                && PromotionTimeZoneService::toLocalTime($promotion, $date)->startOfDay()->greaterThanOrEqualTo($eventDay))
            ->min();
    }

    /**
     * A back-filled winner who was released or retired on the show's day, at or before the show started, cannot take
     * the title: the result is recorded but the title does not change hands. A reign this match already created for
     * the winner is left alone so re-recording the same result never removes it.
     *
     * @param  TitleChampionshipCollection<int, TitleChampionship>  $reigns
     */
    public function winnerLeftBeforeShow(EventMatch $match, Title $title, Wrestler|TagTeam $winner, TitleChampionshipCollection $reigns): bool
    {
        if (! $this->isBackFill($match)) {
            return false;
        }

        $reignWonAtMatch = $reigns->forTitle($title)->wonAt($match);

        if ($this->reignBelongsTo($reignWonAtMatch, $winner)) {
            return false;
        }

        $eventDate = $match->event->date;
        $leftAt = $eventDate instanceof Carbon ? $this->firstLeaveOnOrAfterEventDay($match, $eventDate, $winner) : null;

        return $leftAt instanceof Carbon && $leftAt->lessThanOrEqualTo($eventDate);
    }

    public function endCurrentReign(Title $title, Carbon $endedAt): void
    {
        $reign = TitleChampionship::query()
            ->whereBelongsTo($title)
            ->current()
            ->lockForUpdate()
            ->first();

        $reign?->update(['lost_at' => $this->reignEnd($reign, $endedAt)]);
    }

    /** @param Model&CanBeChampion<*> $champion */
    public function endCurrentReignsForChampion(Model&CanBeChampion $champion, Carbon $endedAt): void
    {
        $champion->currentChampionships()
            ->inLockOrder()
            ->lockForUpdate()
            ->get()
            ->each(fn (TitleChampionship $reign): bool => $reign->update([
                'lost_at' => $this->reignEnd($reign, $endedAt),
            ]));
    }

    /**
     * A win by the champion who held the title on the event date is a defence, even when the title has since moved on
     * or been vacated, so recording it never touches the lineage. Only matches that created no reign can be defences.
     *
     * @param  TitleChampionshipCollection<int, TitleChampionship>  $activeReigns
     */
    private function isDefenceOfReignHeldOnEventDate(
        EventMatch $match,
        ?TitleChampionship $reignWonAtMatch,
        Wrestler|TagTeam|null $desiredChampion,
        TitleChampionshipCollection $activeReigns,
    ): bool {
        $eventDate = $match->event->date;

        if ($reignWonAtMatch instanceof TitleChampionship || $desiredChampion === null || ! $eventDate instanceof Carbon) {
            return false;
        }

        return $this->reignBelongsTo(
            $activeReigns->covering($eventDate),
            $desiredChampion,
        );
    }

    /** A reign never ends before it began, even when the end date predates a future-dated win. */
    private function reignEnd(TitleChampionship $reign, Carbon $endedAt): Carbon
    {
        return $endedAt->lessThan($reign->won_at) ? $reign->won_at : $endedAt;
    }

    /** A reign is only reopened for a champion who can still hold the title; otherwise the title stays vacant. */
    private function reopenablePredecessor(Title $title, ?TitleChampionship $predecessor): ?TitleChampionship
    {
        if (! $predecessor instanceof TitleChampionship || ! $title->currentActivityPeriod()->exists()) {
            return null;
        }

        $champion = $predecessor->champion()->first();
        $isAvailable = ($champion instanceof Wrestler || $champion instanceof TagTeam)
            && $champion->status === EmploymentStatus::Employed;

        return $isAvailable ? $predecessor : null;
    }

    private function reignBelongsTo(
        ?TitleChampionship $reign,
        Wrestler|TagTeam|null $champion,
    ): bool {
        return $reign instanceof TitleChampionship
            && $champion !== null
            && $reign->champion_type === $champion->getMorphClass()
            && $reign->champion_id === $champion->id;
    }
}
