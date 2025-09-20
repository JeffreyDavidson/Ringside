<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Stables\StableMembershipData;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use InvalidArgumentException;

/**
 * Service for validating stable operations and business rules.
 *
 * Centralizes validation logic for stable operations like creation,
 * updates, splits, merges, and other complex business rules.
 */
class StableValidationService
{
    /**
     * Validate that a stable name is unique.
     *
     * @param  string  $name  The stable name to validate
     * @param  Stable|null  $excludeStable  Stable to exclude from check (for updates)
     * @throws InvalidArgumentException When name is not unique
     */
    public function validateUniqueName(string $name, ?Stable $excludeStable = null): void
    {
        $trimmedName = mb_trim($name);

        if (empty($trimmedName)) {
            throw new InvalidArgumentException('Stable name cannot be empty.');
        }

        $query = Stable::where('name', $trimmedName);

        if ($excludeStable) {
            $query->where('id', '!=', $excludeStable->id);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException("A stable with the name '{$trimmedName}' already exists.");
        }
    }

    /**
     * Validate that a stable can be split.
     *
     * @param  Stable  $stable  The stable to validate for splitting
     * @throws InvalidArgumentException When stable cannot be split
     */
    public function validateCanSplit(Stable $stable): void
    {
        if ($stable->isRetired()) {
            throw new InvalidArgumentException("Cannot split stable '{$stable->name}': stable is retired.");
        }

        if (! $stable->isCurrentlyActive()) {
            throw new InvalidArgumentException("Cannot split stable '{$stable->name}': stable is not currently active.");
        }

        $currentMemberCount = $stable->currentWrestlers->count() + $stable->currentTagTeams->count();
        if ($currentMemberCount < 2) {
            throw new InvalidArgumentException("Cannot split stable '{$stable->name}': requires at least 2 members to split.");
        }
    }

    /**
     * Validate that two stables can be merged.
     *
     * @param  Stable  $primaryStable  The stable receiving members
     * @param  Stable  $secondaryStable  The stable being merged
     * @throws InvalidArgumentException When stables cannot be merged
     */
    public function validateCanMerge(Stable $primaryStable, Stable $secondaryStable): void
    {
        if ($primaryStable->id === $secondaryStable->id) {
            throw new InvalidArgumentException('Cannot merge a stable with itself.');
        }

        if ($primaryStable->isRetired()) {
            throw new InvalidArgumentException("Cannot merge into stable '{$primaryStable->name}': primary stable is retired.");
        }

        if ($secondaryStable->isRetired()) {
            throw new InvalidArgumentException("Cannot merge stable '{$secondaryStable->name}': secondary stable is retired.");
        }

        if (! $primaryStable->isCurrentlyActive()) {
            throw new InvalidArgumentException("Cannot merge into stable '{$primaryStable->name}': primary stable is not active.");
        }

        if (! $secondaryStable->isCurrentlyActive()) {
            throw new InvalidArgumentException("Cannot merge stable '{$secondaryStable->name}': secondary stable is not active.");
        }
    }

    /**
     * Validate that members are available for stable operations.
     *
     * @param  StableMembershipData  $members  The members to validate
     * @throws InvalidArgumentException When members are not available
     */
    public function validateMembersAvailable(StableMembershipData $members): void
    {
        if ($members->isEmpty()) {
            throw new InvalidArgumentException('Cannot create stable: at least one member is required.');
        }

        // Validate wrestlers are employed
        if ($members->hasWrestlers()) {
            $unemployedWrestlers = $members->wrestlers->filter(fn (Wrestler $wrestler) => ! $wrestler->isEmployed());
            if ($unemployedWrestlers->isNotEmpty()) {
                $names = $unemployedWrestlers->pluck('name')->join(', ');
                throw new InvalidArgumentException("Cannot add wrestlers to stable: the following wrestlers are not employed: {$names}");
            }
        }

        // Validate tag teams are employed
        if ($members->hasTagTeams()) {
            $unemployedTagTeams = $members->tagTeams->filter(fn (TagTeam $tagTeam) => ! $tagTeam->isEmployed());
            if ($unemployedTagTeams->isNotEmpty()) {
                $names = $unemployedTagTeams->pluck('name')->join(', ');
                throw new InvalidArgumentException("Cannot add tag teams to stable: the following tag teams are not employed: {$names}");
            }
        }
    }

    /**
     * Validate that a stable can have its establishment date changed.
     *
     * @param  Stable  $stable  The stable to validate
     * @throws InvalidArgumentException When establishment date cannot be changed
     */
    public function validateEstablishmentDateChange(Stable $stable): void
    {
        if ($stable->isCurrentlyActive() && ! $stable->hasFutureActivity()) {
            throw new InvalidArgumentException("Establishment date cannot be changed for stable '{$stable->name}' that is currently active.");
        }
    }

    /**
     * Validate that split members are feasible.
     *
     * @param  Stable  $originalStable  The stable being split
     * @param  array  $membersForNewStable  The members being moved
     * @throws InvalidArgumentException When split is not feasible
     */
    public function validateSplitMembers(Stable $originalStable, array $membersForNewStable): void
    {
        $totalMembersBeingSplit = 0;

        if (isset($membersForNewStable['wrestlers'])) {
            $totalMembersBeingSplit += count($membersForNewStable['wrestlers']);
        }

        if (isset($membersForNewStable['tagTeams'])) {
            $totalMembersBeingSplit += count($membersForNewStable['tagTeams']);
        }

        if ($totalMembersBeingSplit === 0) {
            throw new InvalidArgumentException('Cannot split stable: at least one member must be moved to the new stable.');
        }

        $totalCurrentMembers = $originalStable->currentWrestlers->count() + $originalStable->currentTagTeams->count();
        $remainingMembers = $totalCurrentMembers - $totalMembersBeingSplit;

        if ($remainingMembers === 0) {
            throw new InvalidArgumentException('Cannot split stable: at least one member must remain in the original stable.');
        }
    }
}
