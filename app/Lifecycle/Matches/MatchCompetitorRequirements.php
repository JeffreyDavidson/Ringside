<?php

declare(strict_types=1);

namespace App\Lifecycle\Matches;

use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Models\Matches\EventMatch;
use App\ValueObjects\MatchSides;

final class MatchCompetitorRequirements
{
    /**
     * Refuse sides that do not suit the match type. The checks run in this order, so the first rule broken is the
     * one reported: side count, competitor count, competitor types, duplicates, entries per side, individual
     * sides, represented members, roster members per side.
     *
     * @throws InvalidMatchConfigurationException
     */
    public function ensureSatisfied(EventMatch $match, MatchSides $sides): void
    {
        $populatedSides = $sides->populated();

        $requiredSides = $match->match_type->numberOfSides();

        if ($requiredSides !== null && $populatedSides->count() !== $requiredSides) {
            throw InvalidMatchConfigurationException::incorrectSideCount($requiredSides);
        }

        if ($match->match_type->usesIndividualCompetitorSides()) {
            $competitorCount = $populatedSides->competitors()->count();
            $minimumCompetitors = $match->match_type->getMinimumCompetitors();
            $maximumCompetitors = $match->match_type->getMaximumCompetitors();

            if ($competitorCount < $minimumCompetitors || ($maximumCompetitors !== null && $competitorCount > $maximumCompetitors)) {
                throw InvalidMatchConfigurationException::invalidCompetitorCount(
                    $minimumCompetitors,
                    $maximumCompetitors,
                );
            }
        }

        $wrestlers = $populatedSides->wrestlers();
        $tagTeams = $populatedSides->tagTeams();

        if (($wrestlers->isNotEmpty() && ! $match->match_type->allowsWrestlers())
            || ($tagTeams->isNotEmpty() && ! $match->match_type->allowsTagTeams())) {
            throw InvalidMatchConfigurationException::unsupportedCompetitorType($match->match_type);
        }

        if ($wrestlers->count() !== $wrestlers->unique('id')->count()
            || $tagTeams->count() !== $tagTeams->unique('id')->count()) {
            throw InvalidMatchConfigurationException::duplicateCompetitors();
        }

        $actualCompetitorEntriesPerSide = $populatedSides->entriesPerSide();
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

        $tagTeams->loadMissing('currentWrestlers');

        if ($wrestlers->pluck('id')->intersect($tagTeams->currentWrestlers()->modelKeys())->isNotEmpty()) {
            throw InvalidMatchConfigurationException::duplicateCompetitorRepresentation();
        }

        $requiredRosterMembersPerSide = $match->match_type->requiredRosterMembersPerSide();

        if ($requiredRosterMembersPerSide === null) {
            return;
        }

        if ($populatedSides->rosterMembersPerSide() !== $requiredRosterMembersPerSide) {
            throw InvalidMatchConfigurationException::invalidSideComposition(
                $match->match_type,
                $requiredRosterMembersPerSide,
            );
        }
    }
}
