<?php

declare(strict_types=1);

namespace App\Lifecycle;

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
        Collection $reigns,
    ): void {
        $reignWonAtMatch = $this->activeReignsForTitle($title, $reigns)
            ->firstWhere('won_match_id', $match->id);

        if ($reignWonAtMatch?->lost_match_id !== null) {
            throw InvalidMatchOutcomeException::titleLineageHasAdvanced();
        }
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

        if ($reignWonAtMatch !== null) {
            $reignWonAtMatch->delete();

            $currentReign = $activeReigns->firstWhere('lost_match_id', $match->id);
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
        TitleChampionship::query()
            ->whereBelongsTo($title)
            ->current()
            ->lockForUpdate()
            ->first()
            ?->update(['lost_at' => $endedAt]);
    }

    /** @param Model&CanBeChampion<*> $champion */
    public function endCurrentReignsForChampion(Model&CanBeChampion $champion, Carbon $endedAt): void
    {
        $champion->currentChampionships()
            ->lockForUpdate()
            ->get()
            ->each->update(['lost_at' => $endedAt]);
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

    private function reignBelongsTo(
        ?TitleChampionship $reign,
        Wrestler|TagTeam|null $champion,
    ): bool {
        return $reign !== null
            && $champion !== null
            && $reign->champion_type === $champion->getMorphClass()
            && $reign->champion_id === $champion->id;
    }
}
