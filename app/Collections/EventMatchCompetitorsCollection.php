<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\Matches\EventMatchCompetitor;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Custom collection for EventMatchCompetitor models.
 *
 * Provides specialized methods for working with match competitors,
 * including filtering by sides, grouping, and extracting competitor models.
 *
 * @extends Collection<int, EventMatchCompetitor>
 *
 * @example
 * ```php
 * $competitors = new EventMatchCompetitorsCollection($matchCompetitors);
 * $sides = $competitors->sides();
 * $wrestlersOnly = $competitors->onlyWrestlers();
 * ```
 */
class EventMatchCompetitorsCollection extends Collection
{
    /**
     * Sort the competitors collection in ascending order based on their assigned side number.
     *
     * @return static The sorted collection
     *
     * @example
     * ```php
     * $sortedCompetitors = $competitors->sortBySideNumber();
     * ```
     */
    public function sortBySideNumber(): static
    {
        return $this->sortBy('side_number')->values();
    }

    /**
     * Get all unique side numbers.
     *
     * @return array<int> Array of unique side numbers
     *
     * @example
     * ```php
     * $sides = $competitors->sides(); // [1, 2]
     * ```
     */
    public function sides(): array
    {
        return $this->pluck('side_number')->unique()->values()->all();
    }

    /**
     * Count how many competitors are on each side.
     *
     * @return BaseCollection<int, int> Collection mapping side numbers to competitor counts
     *
     * @example
     * ```php
     * $counts = $competitors->countPerSide(); // [1 => 2, 2 => 1]
     * ```
     */
    public function countPerSide(): BaseCollection
    {
        return $this->groupBy('side_number')->map->count();
    }

    /**
     * Get all distinct side numbers present in the collection.
     *
     * Alias for sides() method for backward compatibility.
     *
     * @return array<int> Array of unique side numbers
     */
    public function getSides(): array
    {
        return $this->sides();
    }

    /**
     * Count how many competitors exist for a given side.
     *
     * @param  int  $side  The side number to count
     * @return int Number of competitors on the specified side
     *
     * @example
     * ```php
     * $count = $competitors->countCompetitorsForSide(1); // 2
     * ```
     */
    public function countCompetitorsForSide(int $side): int
    {
        return $this->where('side_number', $side)->count();
    }

    /**
     * Determine if any competitor on the given side is a tag team.
     *
     * @param  int  $side  The side number to check
     * @return bool True if any competitor on the side is a tag team
     *
     * @example
     * ```php
     * $hasTagTeams = $competitors->hasTagTeamsOnSide(1);
     * ```
     */
    public function hasTagTeamsOnSide(int $side): bool
    {
        return $this->getCompetitorsForSide($side)
            ->contains(fn (Wrestler|TagTeam $competitor) => $competitor instanceof TagTeam);
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
        return $this->every(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor()->isBookable());
    }

    /**
     * Group competitors by their side number.
     *
     * @return BaseCollection<int, static> Collection of competitors grouped by side
     *
     * @example
     * ```php
     * $grouped = $competitors->groupBySide();
     * // [1 => Collection[...], 2 => Collection[...]]
     * ```
     */
    public function groupBySide(): BaseCollection
    {
        return $this->groupBy('side_number');
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
        return $this->map(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor());
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
        return $this->filter(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor() instanceof Wrestler
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
        return $this->filter(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor() instanceof TagTeam
        );
    }

    /**
     * Get all competitors belonging to a specific side.
     *
     * @param  int  $side  The side number to filter by
     * @return static Collection of competitors on the specified side
     *
     * @example
     * ```php
     * $sideOneCompetitors = $competitors->filterBySide(1);
     * ```
     */
    public function filterBySide(int $side): static
    {
        return $this->filter(fn (EventMatchCompetitor $competitor) => $competitor->side_number === $side);
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
        return $this->map(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor());
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
        return $this->filter(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor() instanceof Wrestler
        )->map(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor());
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
        return $this->filter(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor() instanceof TagTeam
        )->map(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor());
    }

    /**
     * Group actual competitor models (Wrestler or TagTeam) by side number.
     *
     * @return BaseCollection<int, BaseCollection<int, Wrestler|TagTeam>> Competitors grouped by side
     *
     * @example
     * ```php
     * $competitorsBySide = $competitors->pluckCompetitorsBySide();
     * // [1 => Collection[Wrestler, TagTeam], 2 => Collection[Wrestler]]
     * ```
     */
    public function pluckCompetitorsBySide(): BaseCollection
    {
        return $this->groupBy('side_number')
            ->map(function ($competitorsOnSide) {
                return collect($competitorsOnSide)
                    ->filter(fn (mixed $competitor) => $competitor instanceof EventMatchCompetitor)
                    ->map(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor())
                    ->values(); // Reset keys to sequential integers
            });
    }

    /**
     * Get all Bookable competitors for a given side number.
     *
     * @param  int  $side  The side number to get competitors for
     * @return BaseCollection<int, Wrestler|TagTeam> Collection of competitor models
     *
     * @example
     * ```php
     * $sideCompetitors = $competitors->getCompetitorsForSide(1);
     * ```
     */
    public function getCompetitorsForSide(int $side): BaseCollection
    {
        return $this->where('side_number', $side)
            ->map(fn (EventMatchCompetitor $competitor) => $competitor->getCompetitor())
            ->values();
    }
}
