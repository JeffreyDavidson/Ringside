<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\MatchResultData;
use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApplyMatchTitleOutcomesAction
{
    public function handle(EventMatch $match, MatchResultData $result): void
    {
        DB::transaction(function () use ($match, $result): void {
            $lockedMatch = EventMatch::query()
                ->with('event')
                ->lockForUpdate()
                ->findOrFail($match->id);
            $titleIds = $lockedMatch->titles()->pluck((new Title())->qualifyColumn('id'));

            if ($titleIds->isEmpty()) {
                return;
            }

            $titles = Title::query()
                ->whereKey($titleIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $reigns = TitleChampionship::query()
                ->withTrashed()
                ->whereIn('title_id', $titleIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $winningCompetitors = $this->winningCompetitors($lockedMatch, $result);

            /** @var array<int, Wrestler|TagTeam|null> $desiredChampions */
            $desiredChampions = [];

            foreach ($titles as $title) {
                $desiredChampions[$title->id] = $result->finish->allowsTitleChange()
                    ? $this->championForTitle($title, $winningCompetitors)
                    : null;

                $this->ensureLineageCanBeReconciled($lockedMatch, $title, $reigns);
            }

            foreach ($titles as $title) {
                $this->reconcileTitle(
                    $lockedMatch,
                    $title,
                    $desiredChampions[$title->id],
                    $reigns,
                );
            }
        });
    }

    /** @return Collection<int, MatchCompetitor> */
    private function winningCompetitors(EventMatch $match, MatchResultData $result): Collection
    {
        if (! $result->finish->allowsTitleChange() || $result->winningSide === null) {
            return new Collection();
        }

        return MatchCompetitor::query()
            ->whereBelongsTo($match, 'eventMatch')
            ->where('match_side_id', $result->winningSide->id)
            ->with('competitor')
            ->get();
    }

    /**
     * @param  Collection<int, MatchCompetitor>  $winningCompetitors
     */
    private function championForTitle(Title $title, Collection $winningCompetitors): Wrestler|TagTeam
    {
        $eligibleCompetitors = $winningCompetitors
            ->map(fn (MatchCompetitor $competitor): Wrestler|TagTeam => $competitor->competitor)
            ->filter(fn (Wrestler|TagTeam $competitor): bool => match ($title->type) {
                TitleType::Singles => $competitor instanceof Wrestler,
                TitleType::TagTeam => $competitor instanceof TagTeam,
            })
            ->values();

        if ($eligibleCompetitors->count() !== 1) {
            throw InvalidMatchOutcomeException::invalidTitleWinner($title->type);
        }

        return $eligibleCompetitors->sole();
    }

    /** @param Collection<int, TitleChampionship> $reigns */
    private function ensureLineageCanBeReconciled(
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
    private function reconcileTitle(
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
