<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Data\Matches\MatchResultData;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Collection;

class ApplyMatchTitleOutcomesAction
{
    public function __construct(private readonly ChampionshipReignManager $championshipReigns) {}

    /** @param Collection<int, MatchCompetitor> $competitors */
    public function handle(EventMatch $match, MatchResultData $result, Collection $competitors): void
    {
        $titleIds = $match->titles()->pluck((new Title())->qualifyColumn('id'));

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
        $winningCompetitors = $this->winningCompetitors($result, $competitors);

        /** @var array<int, Wrestler|TagTeam|null> $desiredChampions */
        $desiredChampions = [];

        foreach ($titles as $title) {
            $desiredChampions[$title->id] = $result->finish->allowsTitleChange()
                ? $this->championForTitle($title, $winningCompetitors)
                : null;

            $this->championshipReigns->ensureMatchCanBeReconciled($match, $title, $reigns);
        }

        foreach ($titles as $title) {
            $this->championshipReigns->reconcileMatchOutcome(
                $match,
                $title,
                $desiredChampions[$title->id],
                $reigns,
            );
        }
    }

    /**
     * @param  Collection<int, MatchCompetitor>  $competitors
     * @return Collection<int, MatchCompetitor>
     */
    private function winningCompetitors(MatchResultData $result, Collection $competitors): Collection
    {
        if (! $result->finish->allowsTitleChange() || $result->winningSide === null) {
            return new Collection();
        }

        return $competitors
            ->where('match_side_id', $result->winningSide->id)
            ->values();
    }

    /**
     * @param  Collection<int, MatchCompetitor>  $winningCompetitors
     */
    private function championForTitle(Title $title, Collection $winningCompetitors): Wrestler|TagTeam
    {
        $eligibleCompetitors = $winningCompetitors
            ->map(fn (MatchCompetitor $competitor): Wrestler|TagTeam => $competitor->competitor)
            ->filter(fn (Wrestler|TagTeam $competitor): bool => $competitor instanceof ($title->type->championModelClass()))
            ->values();

        if ($eligibleCompetitors->count() !== 1) {
            throw InvalidMatchOutcomeException::invalidTitleWinner($title->type);
        }

        return $eligibleCompetitors->sole();
    }
}
