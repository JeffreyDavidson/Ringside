<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Collection;

final class MatchCompetitorRequirements
{
    /**
     * @param  Collection<int, covariant array{wrestlers?: array<int, Wrestler>, tag_teams?: array<int, TagTeam>}>  $sides
     */
    public function ensureSatisfied(EventMatch $match, Collection $sides): void
    {
        $populatedSides = $sides->filter(
            fn (array $side): bool => ($side['wrestlers'] ?? []) !== [] || ($side['tag_teams'] ?? []) !== []
        );

        $requiredSides = $match->match_type->numberOfSides();

        if ($requiredSides !== null && $populatedSides->count() !== $requiredSides) {
            throw InvalidMatchConfigurationException::incorrectSideCount($requiredSides);
        }

        if ($match->match_type->usesIndividualCompetitorSides()) {
            $competitorCount = $this->competitors($populatedSides)->count();
            $minimumCompetitors = $match->match_type->getMinimumCompetitors();
            $maximumCompetitors = $match->match_type->getMaximumCompetitors();

            if ($competitorCount < $minimumCompetitors || ($maximumCompetitors !== null && $competitorCount > $maximumCompetitors)) {
                throw InvalidMatchConfigurationException::invalidCompetitorCount(
                    $minimumCompetitors,
                    $maximumCompetitors,
                );
            }
        }

        $competitors = $this->competitors($populatedSides);
        $wrestlers = $competitors->filter(fn (Wrestler|TagTeam $competitor): bool => $competitor instanceof Wrestler);
        $tagTeams = $competitors->filter(fn (Wrestler|TagTeam $competitor): bool => $competitor instanceof TagTeam);

        if ($wrestlers->count() !== $wrestlers->unique('id')->count()
            || $tagTeams->count() !== $tagTeams->unique('id')->count()) {
            throw InvalidMatchConfigurationException::duplicateCompetitors();
        }
    }

    /**
     * @param  Collection<int, covariant array{wrestlers?: array<int, Wrestler>, tag_teams?: array<int, TagTeam>}>  $sides
     * @return Collection<int, Wrestler|TagTeam>
     */
    private function competitors(Collection $sides): Collection
    {
        return $sides
            ->flatMap(fn (array $side): array => [
                ...($side['wrestlers'] ?? []),
                ...($side['tag_teams'] ?? []),
            ])
            ->values();
    }
}
