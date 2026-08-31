<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Collections\MatchCompetitorsCollection;
use App\Data\Matches\MatchResultData;
use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

class ApplyMatchTitleOutcomesAction
{
    public function __construct(private readonly ChampionshipReignManager $championshipReigns) {}

    /** @param MatchCompetitorsCollection<int, MatchCompetitor> $competitors */
    public function handle(EventMatch $match, MatchResultData $result, MatchCompetitorsCollection $competitors): void
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
     * @param  MatchCompetitorsCollection<int, MatchCompetitor>  $competitors
     * @return MatchCompetitorsCollection<int, MatchCompetitor>
     */
    private function winningCompetitors(MatchResultData $result, MatchCompetitorsCollection $competitors): MatchCompetitorsCollection
    {
        if (! $result->finish->allowsTitleChange() || $result->winningSide === null) {
            return new MatchCompetitorsCollection();
        }

        return $competitors
            ->where('match_side_id', $result->winningSide->id)
            ->values();
    }

    /**
     * @param  MatchCompetitorsCollection<int, MatchCompetitor>  $winningCompetitors
     */
    private function championForTitle(Title $title, MatchCompetitorsCollection $winningCompetitors): Wrestler|TagTeam
    {
        $eligibleCompetitors = match ($title->type) {
            TitleType::Singles => $winningCompetitors->wrestlers(),
            TitleType::TagTeam => $winningCompetitors->tagTeams(),
        };

        if ($eligibleCompetitors->count() !== 1) {
            throw InvalidMatchOutcomeException::invalidTitleWinner($title->type);
        }

        return $eligibleCompetitors->sole();
    }
}
