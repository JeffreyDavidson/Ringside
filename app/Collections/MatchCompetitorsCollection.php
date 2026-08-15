<?php

declare(strict_types=1);

namespace App\Collections;

use App\Lifecycle\RosterBookingEligibility;
use App\Models\Matches\MatchCompetitor;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Custom collection for MatchCompetitor models.
 *
 * Provides specialized methods for working with match competitors,
 * including filtering by sides, grouping, and extracting competitor models.
 *
 * @extends Collection<int, MatchCompetitor>
 *
 * @example
 * ```php
 * $competitors = new MatchCompetitorsCollection($matchCompetitors);
 * $sidePositions = $competitors->sidePositions();
 * $wrestlersOnly = $competitors->onlyWrestlers();
 * ```
 */
class MatchCompetitorsCollection extends Collection
{
    /**
     * Sort the competitors collection in ascending order based on their assigned side number.
     *
     * @return static The sorted collection
     *
     * @example
     * ```php
     * $sortedCompetitors = $competitors->sortBySidePosition();
     * ```
     */
    public function sortBySidePosition(): static
    {
        return $this->sortBy(fn (MatchCompetitor $competitor): int => $competitor->side->position)->values();
    }

    /**
     * Get all unique side numbers.
     *
     * @return array<int> Array of unique side numbers
     *
     * @example
     * ```php
     * $sidePositions = $competitors->sidePositions(); // [1, 2]
     * ```
     */
    public function sidePositions(): array
    {
        return $this->map(fn (MatchCompetitor $competitor): int => $competitor->side->position)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Count how many competitors are on each side.
     *
     * @return BaseCollection<int, int> Collection mapping side numbers to competitor counts
     *
     * @example
     * ```php
     * $counts = $competitors->countBySidePosition(); // [1 => 2, 2 => 1]
     * ```
     */
    public function countBySidePosition(): BaseCollection
    {
        // @phpstan-ignore-next-line return.type
        return $this->groupBy(fn (MatchCompetitor $competitor): int => $competitor->side->position)
            ->map(fn (MatchCompetitorsCollection $group) => $group->count())
            ->mapWithKeys(function (int $count, mixed $side): array {
                return [(int) $side => $count];
            });
    }

    /**
     * Count how many competitors exist for a given side.
     *
     * @param  int  $position  The side position to count
     * @return int Number of competitors on the specified side
     *
     * @example
     * ```php
     * $count = $competitors->competitorCountForSidePosition(1); // 2
     * ```
     */
    public function competitorCountForSidePosition(int $position): int
    {
        return $this->forSidePosition($position)->count();
    }

    /**
     * Determine if any competitor on the given side is a tag team.
     *
     * @param  int  $position  The side position to check
     * @return bool True if any competitor on the side is a tag team
     *
     * @example
     * ```php
     * $hasTagTeams = $competitors->hasTagTeamsOnSidePosition(1);
     * ```
     */
    public function hasTagTeamsOnSidePosition(int $position): bool
    {
        return $this->competitorsForSidePosition($position)
            ->contains(fn (Wrestler|TagTeam $competitor): bool => $competitor instanceof TagTeam);
    }

    /**
     * Check if all competitors in the collection are bookable.
     *
     * @return bool True if all competitors are bookable
     *
     * @example
     * ```php
     * if ($competitors->allBookable()) {
     *     echo "All competitors are available for booking";
     * }
     * ```
     */
    public function allBookable(): bool
    {
        return $this->every(fn (MatchCompetitor $competitor) => RosterBookingEligibility::allows($competitor->competitor));
    }

    /**
     * Group competitors by their side number.
     *
     * @return BaseCollection<int, static> Collection of competitors grouped by side
     *
     * @example
     * ```php
     * $grouped = $competitors->groupBySidePosition();
     * // [1 => Collection[...], 2 => Collection[...]]
     * ```
     */
    public function groupBySidePosition(): BaseCollection
    {
        return $this->groupBy(fn (MatchCompetitor $competitor): int => $competitor->side->position);
    }

    /**
     * Map the collection to the resolved Bookable competitors (Wrestler or TagTeam).
     *
     * @return BaseCollection<int, Wrestler|TagTeam> Collection of competitor models
     *
     * @example
     * ```php
     * $competitorModels = $competitors->mapToCompetitorInstances();
     * ```
     */
    public function mapToCompetitorInstances(): BaseCollection
    {
        return $this->map(fn (MatchCompetitor $competitor) => $competitor->competitor);
    }

    /**
     * Get only the competitors that are Wrestlers.
     *
     * @return static Collection containing only wrestler competitors
     *
     * @example
     * ```php
     * $wrestlerCompetitors = $competitors->onlyWrestlers();
     * ```
     */
    public function onlyWrestlers(): static
    {
        return $this->filter(fn (MatchCompetitor $competitor) => $competitor->competitor instanceof Wrestler
        );
    }

    /**
     * Get only the competitors that are Tag Teams.
     *
     * @return static Collection containing only tag team competitors
     *
     * @example
     * ```php
     * $tagTeamCompetitors = $competitors->onlyTagTeams();
     * ```
     */
    public function onlyTagTeams(): static
    {
        return $this->filter(fn (MatchCompetitor $competitor) => $competitor->competitor instanceof TagTeam
        );
    }

    /**
     * Get all competitors belonging to a specific side.
     *
     * @param  int  $position  The side position to filter by
     * @return static Collection of competitors on the specified side
     *
     * @example
     * ```php
     * $sideOneCompetitors = $competitors->forSidePosition(1);
     * ```
     */
    public function forSidePosition(int $position): static
    {
        return $this->filter(fn (MatchCompetitor $competitor): bool => $competitor->side->position === $position);
    }

    /**
     * Pluck the underlying competitor models (Wrestler or TagTeam).
     *
     * @return BaseCollection<int, Wrestler|TagTeam> Collection of competitor models
     *
     * @example
     * ```php
     * $competitorModels = $competitors->pluckCompetitors();
     * ```
     */
    public function pluckCompetitors(): BaseCollection
    {
        return $this->map(fn (MatchCompetitor $competitor) => $competitor->competitor);
    }

    /**
     * Pluck only the Wrestler models.
     *
     * @return BaseCollection<int, Wrestler> Collection of wrestler models
     *
     * @example
     * ```php
     * $wrestlers = $competitors->pluckWrestlers();
     * ```
     */
    public function pluckWrestlers(): BaseCollection
    {
        return $this->filter(fn (MatchCompetitor $competitor) => $competitor->competitor instanceof Wrestler
        )->map(fn (MatchCompetitor $competitor) => $competitor->competitor);
    }

    /**
     * Pluck only the TagTeam models.
     *
     * @return BaseCollection<int, TagTeam> Collection of tag team models
     *
     * @example
     * ```php
     * $tagTeams = $competitors->pluckTagTeams();
     * ```
     */
    public function pluckTagTeams(): BaseCollection
    {
        return $this->filter(fn (MatchCompetitor $competitor) => $competitor->competitor instanceof TagTeam
        )->map(fn (MatchCompetitor $competitor) => $competitor->competitor);
    }

    /**
     * Group actual competitor models (Wrestler or TagTeam) by side number.
     *
     * @return BaseCollection<int, BaseCollection<int, Wrestler|TagTeam>> Competitors grouped by side
     *
     * @example
     * ```php
     * $competitorsBySide = $competitors->competitorsBySidePosition();
     * // [1 => Collection[Wrestler, TagTeam], 2 => Collection[Wrestler]]
     * ```
     */
    public function competitorsBySidePosition(): BaseCollection
    {
        return $this->groupBy(fn (MatchCompetitor $competitor): int => $competitor->side->position)
            ->map(function (MatchCompetitorsCollection $competitorsOnSide) {
                return collect($competitorsOnSide)
                    ->map(fn (MatchCompetitor $competitor) => $competitor->competitor)
                    ->values(); // Reset keys to sequential integers
            });
    }

    /**
     * Get all Bookable competitors for a given side number.
     *
     * @param  int  $position  The side position to get competitors for
     * @return BaseCollection<int, Wrestler|TagTeam> Collection of competitor models
     *
     * @example
     * ```php
     * $sideCompetitors = $competitors->competitorsForSidePosition(1);
     * ```
     */
    public function competitorsForSidePosition(int $position): BaseCollection
    {
        return $this->forSidePosition($position)
            ->map(fn (MatchCompetitor $competitor): Wrestler|TagTeam => $competitor->competitor)
            ->values();
    }
}
