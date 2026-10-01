<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Collections\MatchCompetitorsCollection;
use App\Data\Matches\MatchResultData;
use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

class ApplyMatchTitleOutcomesAction
{
    public function __construct(
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly RosterBookingEligibility $bookingEligibility,
    ) {}

    /** @param MatchCompetitorsCollection<int, MatchCompetitor> $competitors */
    public function handle(EventMatch $match, MatchResultData $result, MatchCompetitorsCollection $competitors): void
    {
        $titleIds = $match->titles()->withTrashed()->pluck((new Title)->qualifyColumn('id'));

        if ($titleIds->isEmpty()) {
            return;
        }

        $titles = Title::query()
            ->withTrashed()
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

        if ($result->finish->allowsTitleChange() && $titles->contains(fn (Title $title): bool => $title->trashed())) {
            throw InvalidMatchOutcomeException::titleDeleted();
        }

        $titles = $titles->reject(fn (Title $title): bool => $title->trashed());

        foreach ($titles as $title) {
            $desiredChampion = $result->finish->allowsTitleChange()
                ? $this->championForTitle($title, $winningCompetitors)
                : null;
            $desiredChampions[$title->id] = $desiredChampion;

            if ($desiredChampion !== null && $this->championshipReigns->changesChampion($match, $title, $desiredChampion, $reigns)) {
                $this->ensureTitleCanChangeHands($title, $desiredChampion);
            }

            $this->championshipReigns->ensureMatchCanBeReconciled($match, $title, $desiredChampions[$title->id], $reigns);
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

    private function ensureTitleCanChangeHands(Title $title, Wrestler|TagTeam $winner): void
    {
        if (! $title->currentActivityPeriod()->exists()) {
            throw InvalidMatchOutcomeException::titleNotActive($title);
        }

        if (! $this->bookingEligibility->allows($winner)) {
            throw InvalidMatchOutcomeException::winnerNotEligible($winner);
        }
    }

    /**
     * @param  MatchCompetitorsCollection<int, MatchCompetitor>  $competitors
     * @return MatchCompetitorsCollection<int, MatchCompetitor>
     */
    private function winningCompetitors(MatchResultData $result, MatchCompetitorsCollection $competitors): MatchCompetitorsCollection
    {
        if (! $result->finish->allowsTitleChange() || ! $result->winningSide instanceof MatchSide) {
            return new MatchCompetitorsCollection;
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
        if ($winningCompetitors->contains(fn (MatchCompetitor $competitor): bool => $competitor->competitor()->doesntExist())) {
            throw InvalidMatchOutcomeException::winnerDeleted();
        }

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
