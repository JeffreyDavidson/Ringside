<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

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

        if (($wrestlers->isNotEmpty() && ! $match->match_type->allowsWrestlers())
            || ($tagTeams->isNotEmpty() && ! $match->match_type->allowsTagTeams())) {
            throw InvalidMatchConfigurationException::unsupportedCompetitorType($match->match_type);
        }

        if ($wrestlers->count() !== $wrestlers->unique('id')->count()
            || $tagTeams->count() !== $tagTeams->unique('id')->count()) {
            throw InvalidMatchConfigurationException::duplicateCompetitors();
        }

        $actualCompetitorEntriesPerSide = $populatedSides
            ->map(fn (array $side): int => count($side['wrestlers'] ?? []) + count($side['tag_teams'] ?? []))
            ->sort()
            ->values()
            ->all();
        $requiredCompetitorEntriesPerSide = $match->match_type->requiredCompetitorEntriesPerSide();

        if ($requiredCompetitorEntriesPerSide !== null
            && $actualCompetitorEntriesPerSide !== $requiredCompetitorEntriesPerSide) {
            throw InvalidMatchConfigurationException::invalidCompetitorEntryComposition(
                $match->match_type,
                $requiredCompetitorEntriesPerSide,
            );
        }

        if ($match->match_type->usesIndividualCompetitorSides()
            && $actualCompetitorEntriesPerSide !== array_fill(0, $populatedSides->count(), 1)) {
            throw InvalidMatchConfigurationException::individualCompetitorSidesRequired($match->match_type);
        }

        (new TagTeam())->newCollection($tagTeams->all())->loadMissing('currentWrestlers');
        $representedWrestlerIds = $tagTeams
            ->flatMap(fn (TagTeam $tagTeam): array => $tagTeam->currentWrestlers->modelKeys());

        if ($wrestlers->pluck('id')->intersect($representedWrestlerIds)->isNotEmpty()) {
            throw InvalidMatchConfigurationException::duplicateCompetitorRepresentation();
        }

        $requiredRosterMembersPerSide = $match->match_type->requiredRosterMembersPerSide();

        if ($requiredRosterMembersPerSide === null) {
            return;
        }

        $actualRosterMembersPerSide = $populatedSides
            ->map(function (array $side): int {
                $tagTeamMembers = collect($side['tag_teams'] ?? [])
                    ->sum(fn (TagTeam $tagTeam): int => $tagTeam->currentWrestlers->count());

                return count($side['wrestlers'] ?? []) + $tagTeamMembers;
            })
            ->sort()
            ->values()
            ->all();

        if ($actualRosterMembersPerSide !== $requiredRosterMembersPerSide) {
            throw InvalidMatchConfigurationException::invalidSideComposition(
                $match->match_type,
                $requiredRosterMembersPerSide,
            );
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
