<?php

declare(strict_types=1);

namespace App\Lifecycle\Titles;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Contracts\CanBeChampion;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class ChampionshipReignManager
{
    /** @param Collection<int, TitleChampionship> $reigns */
    public function ensureMatchCanBeReconciled(
        EventMatch $match,
        Title $title,
        Wrestler|TagTeam|null $desiredChampion,
        Collection $reigns,
    ): void {
        $activeReigns = $this->activeReignsForTitle($title, $reigns);
        $reignWonAtMatch = $activeReigns->firstWhere('won_match_id', $match->id);

        if ($reignWonAtMatch?->lost_match_id !== null) {
            throw InvalidMatchOutcomeException::titleLineageHasAdvanced();
        }

        if ($this->reignBelongsTo($reignWonAtMatch, $desiredChampion)) {
            return;
        }

        $eventDate = $match->event->date;

        if (! $eventDate instanceof Carbon || ($reignWonAtMatch === null && $desiredChampion === null)) {
            return;
        }

        if ($this->isDefenceOfReignHeldOnEventDate($match, $reignWonAtMatch, $desiredChampion, $activeReigns)) {
            return;
        }

        $laterReignExists = $activeReigns->contains(
            fn (TitleChampionship $reign): bool => $reign->won_match_id !== $match->id
                && $reign->won_at->greaterThan($eventDate),
        );

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
     * @param  Collection<int, TitleChampionship>  $reigns
     */
    public function changesChampion(
        EventMatch $match,
        Title $title,
        Wrestler|TagTeam|null $desiredChampion,
        Collection $reigns,
    ): bool {
        if ($desiredChampion === null) {
            return false;
        }

        $activeReigns = $this->activeReignsForTitle($title, $reigns);
        $reignWonAtMatch = $activeReigns->firstWhere('won_match_id', $match->id);

        if ($reignWonAtMatch !== null) {
            return ! $this->reignBelongsTo($reignWonAtMatch, $desiredChampion);
        }

        if ($this->isDefenceOfReignHeldOnEventDate($match, null, $desiredChampion, $activeReigns)) {
            return false;
        }

        return ! $this->reignBelongsTo(
            $activeReigns->whereNull('lost_at')->sortByDesc('won_at')->first(),
            $desiredChampion,
        );
    }

    /** @param Collection<int, TitleChampionship> $reigns */
    public function reconcileMatchOutcome(
        EventMatch $match,
        Title $title,
        Wrestler|TagTeam|null $desiredChampion,
        Collection $reigns,
    ): void {
        $activeReigns = $this->activeReignsForTitle($title, $reigns);
        $reignWonAtMatch = $activeReigns->firstWhere('won_match_id', $match->id);
        $currentReign = $activeReigns
            ->whereNull('lost_at')
            ->sortByDesc('won_at')
            ->first();

        if ($reignWonAtMatch !== null && $this->reignBelongsTo($reignWonAtMatch, $desiredChampion)) {
            return;
        }

        if ($this->isDefenceOfReignHeldOnEventDate($match, $reignWonAtMatch, $desiredChampion, $activeReigns)) {
            return;
        }

        if ($reignWonAtMatch !== null) {
            $reignWonAtMatch->delete();

            $currentReign = $this->reopenablePredecessor($title, $activeReigns->firstWhere('lost_match_id', $match->id));
            $currentReign?->update([
                'lost_match_id' => null,
                'lost_at' => null,
            ]);
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

        TitleChampionship::query()->create([
            'title_id' => $title->id,
            'champion_type' => $desiredChampion->getMorphClass(),
            'champion_id' => $desiredChampion->id,
            'won_match_id' => $match->id,
            'won_at' => $eventDate,
        ]);
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
     * @param  Collection<int, TitleChampionship>  $reigns
     * @return Collection<int, TitleChampionship>
     */
    private function activeReignsForTitle(Title $title, Collection $reigns): Collection
    {
        return $reigns
            ->where('title_id', $title->id)
            ->filter(fn (TitleChampionship $reign): bool => $reign->deleted_at === null);
    }

    /**
     * A win by the champion who held the title on the event date is a defence, even when the title has since moved on
     * or been vacated, so recording it never touches the lineage. Only matches that created no reign can be defences.
     *
     * @param  Collection<int, TitleChampionship>  $activeReigns
     */
    private function isDefenceOfReignHeldOnEventDate(
        EventMatch $match,
        ?TitleChampionship $reignWonAtMatch,
        Wrestler|TagTeam|null $desiredChampion,
        Collection $activeReigns,
    ): bool {
        $eventDate = $match->event->date;

        if ($reignWonAtMatch instanceof TitleChampionship || $desiredChampion === null || ! $eventDate instanceof Carbon) {
            return false;
        }

        return $this->reignBelongsTo(
            $activeReigns->first(
                fn (TitleChampionship $reign): bool => $reign->won_at->lessThanOrEqualTo($eventDate)
                    && ($reign->lost_at === null || $reign->lost_at->greaterThan($eventDate)),
            ),
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
