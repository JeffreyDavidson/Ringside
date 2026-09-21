<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Collections\MatchCompetitorsCollection;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Collection;

final class MatchTitleRequirements
{
    /** @param Collection<int, Title> $titles */
    public function ensureSatisfied(EventMatch $match, Collection $titles): void
    {
        $this->ensureTitleTypesMatchCompetitors($match, $titles);
        $this->ensureCurrentChampionsCompete($match, $titles);
    }

    /** @param Collection<int, Title> $titles */
    private function ensureTitleTypesMatchCompetitors(EventMatch $match, Collection $titles): void
    {
        $assignedCompetitorTypes = $match->competitors()
            ->pluck('competitor_type')
            ->unique();

        foreach ($titles as $title) {
            if ($assignedCompetitorTypes->count() === 1
                && $assignedCompetitorTypes->contains($title->type->championMorphClass())) {
                continue;
            }

            throw InvalidMatchConfigurationException::titleCompetitorTypeMismatch($title);
        }
    }

    /** @param Collection<int, Title> $titles */
    private function ensureCurrentChampionsCompete(EventMatch $match, Collection $titles): void
    {
        /** @var MatchCompetitorsCollection<int, MatchCompetitor> $assignedCompetitors */
        $assignedCompetitors = $match->competitors()->get(['competitor_type', 'competitor_id']);
        $currentChampionships = TitleChampionship::query()
            ->whereIn('title_id', $titles->pluck('id'))
            ->current()
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'title_id', 'champion_type', 'champion_id']);

        foreach ($currentChampionships as $championship) {
            $championCompetes = $assignedCompetitors->containsCompetitor(
                $championship->champion_type,
                $championship->champion_id,
            );

            if ($championCompetes) {
                continue;
            }

            $title = $titles->firstWhere('id', $championship->title_id);

            if ($title instanceof Title) {
                throw InvalidMatchConfigurationException::currentChampionMissing($title);
            }
        }
    }
}
