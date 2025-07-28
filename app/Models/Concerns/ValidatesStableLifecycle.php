<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Stables\CannotBeDeletedException;
use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeMergedException;
use App\Exceptions\Roster\Stables\CannotBeRestoredException;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use Illuminate\Support\Collection;

/**
 * Provides stable lifecycle validation functionality for Stable models.
 *
 * This trait adds validation methods for stable-specific lifecycle transitions including
 * establishment (first-time activation), disbandment, and reuniting.
 *
 * @see HasActivityPeriods For core activation functionality
 *
 * @example
 * ```php
 * class Stable extends Model
 * {
 *     use HasActivityPeriods, ValidatesStableLifecycle;
 * }
 *
 * // Usage:
 * $stable = Stable::find(1);
 * $stable->ensureCanBeEstablished();  // For first-time activation
 * $stable->ensureCanBeDisbanded();    // For disbandment
 * $stable->ensureCanBeDeleted();      // For soft deletion
 * $stable->ensureCanBeSplit();        // For splitting into two stables
 * $stable->ensureCanBeMerged($other); // For merging with another stable
 * $stable->ensureCanBeRestored();     // For restoration from soft deletion
 * $stable->canBeEstablished();        // Returns boolean for establishment
 * $stable->canBeDeleted();            // Returns boolean for deletion
 * $stable->canBeSplit();              // Returns boolean for splitting
 * $stable->canBeRestored();           // Returns boolean for restoration
 * $stable->isDisbanded();             // Returns boolean if disbanded
 * ```
 */
trait ValidatesStableLifecycle
{
    /**
     * Determine if the stable can be established (first-time activation).
     *
     * Checks business rules to determine if establishment is allowed:
     * - Must not already be active
     * - Must not be retired
     *
     * @return bool True if the stable can be established, false otherwise
     */
    public function canBeEstablished(): bool
    {
        return ! $this->isCurrentlyActive() && ! $this->isRetired();
    }

    /**
     * Ensure the stable can be established, throwing an exception if not.
     *
     * @throws CannotBeEstablishedException When establishment is not allowed
     */
    public function ensureCanBeEstablished(): void
    {
        if ($this->isCurrentlyActive()) {
            throw CannotBeEstablishedException::established($this);
        }

        if ($this->isRetired()) {
            throw CannotBeEstablishedException::retired($this);
        }
    }

    /**
     * Determine if the stable can be disbanded.
     *
     * Checks business rules for disbandment:
     * - Must not be unactivated (never been activated)
     * - Must not already be disbanded
     * - Must not have future activation
     * - Must not be retired
     *
     * @return bool True if the stable can be disbanded, false otherwise
     */
    public function canBeDisbanded(): bool
    {
        return $this->hasActivityPeriods()
            && $this->isCurrentlyActive()
            && ! $this->hasFutureActivation()
            && ! $this->isRetired();
    }

    /**
     * Ensure the stable can be disbanded, throwing an exception if not.
     *
     * @throws CannotBeDisbandedException When disbandment is not allowed
     */
    public function ensureCanBeDisbanded(): void
    {
        if (! $this->hasActivityPeriods()) {
            throw CannotBeDisbandedException::unactivated($this);
        }

        if (! $this->isCurrentlyActive()) {
            throw CannotBeDisbandedException::disbanded($this);
        }

        if ($this->hasFutureActivation()) {
            throw CannotBeDisbandedException::hasFutureActivation($this);
        }

        if ($this->isRetired()) {
            throw CannotBeDisbandedException::retired($this);
        }
    }

    /**
     * Determine if the stable can be reunited (reactivated after disbandment).
     *
     * Checks business rules to determine if reuniting is allowed:
     * - Must have previous activity periods (has been active before)
     * - Must not currently be active
     * - Must not be retired
     *
     * @return bool True if the stable can be reunited, false otherwise
     */
    public function canBeReunited(): bool
    {
        return $this->hasActivityPeriods()
            && ! $this->isCurrentlyActive()
            && ! $this->isRetired();
    }

    /**
     * Ensure the stable can be reunited, throwing an exception if not.
     *
     * @throws CannotBeEstablishedException When reuniting is not allowed
     */
    public function ensureCanBeReunited(): void
    {
        if (! $this->hasActivityPeriods()) {
            throw CannotBeEstablishedException::established($this);
        }

        if ($this->isCurrentlyActive()) {
            throw CannotBeEstablishedException::established($this);
        }

        if ($this->isRetired()) {
            throw CannotBeEstablishedException::retired($this);
        }

        // Check if enough former members are available for reunion
        $availableFormerMembers = $this->getAvailableFormerMembers();
        if ($availableFormerMembers->count() < static::MIN_MEMBERS_COUNT) {
            throw CannotBeEstablishedException::insufficientFormerMembers(
                $this,
                static::MIN_MEMBERS_COUNT,
                $availableFormerMembers->count()
            );
        }

        // Check if key former members are available (not retired, injured, or employed elsewhere)
        $unavailableKeyMembers = $this->getUnavailableKeyFormerMembers();
        if ($unavailableKeyMembers->isNotEmpty()) {
            $memberNames = $unavailableKeyMembers->pluck('name')->join(', ');
            throw CannotBeEstablishedException::keyFormerMembersUnavailable($this, $memberNames);
        }
    }

    /**
     * Determine if the stable can be soft deleted.
     *
     * Checks business rules for soft deletion (operational constraints):
     * - Should not be currently active (use disband first for proper workflow)
     * - Should not have current members (remove members first for clean operation)
     *
     * Note: Soft deletion preserves all historical data and can be restored.
     * This validation focuses on current operational state rather than data preservation.
     *
     * @return bool True if the stable can be soft deleted, false otherwise
     */
    public function canBeDeleted(): bool
    {
        return ! $this->isCurrentlyActive() && ! $this->hasCurrentMembers();
    }

    /**
     * Ensure the stable can be soft deleted, throwing an exception if not.
     *
     * Validates operational constraints for soft deletion. Since soft deletion
     * is recoverable and preserves all data, validation focuses on current
     * operational state to ensure proper stable management workflows.
     *
     * @throws CannotBeDeletedException When soft deletion is not allowed
     */
    public function ensureCanBeDeleted(): void
    {
        if ($this->isCurrentlyActive()) {
            throw CannotBeDeletedException::currentlyActive($this);
        }

        if ($this->hasCurrentMembers()) {
            $memberCount = $this->getCurrentMembersData()->getTotalMemberCount();
            throw CannotBeDeletedException::hasCurrentMembers($this, $memberCount);
        }

        // Additional business rule validations could be added here:
        // - Active championship reigns by members
        // - Ongoing storylines or feuds
        // - Scheduled upcoming events
        // - Administrative authorization requirements
    }

    /**
     * Determine if the stable can be split into two stables.
     *
     * Checks business rules for stable splitting:
     * - Must not be retired
     * - Must be currently active
     * - Must have at least 2 members for viable split
     *
     * @return bool True if the stable can be split, false otherwise
     */
    public function canBeSplit(): bool
    {
        if ($this->isRetired() || ! $this->isCurrentlyActive()) {
            return false;
        }

        $currentMemberCount = $this->currentWrestlers->count() + $this->currentTagTeams->count();

        return $currentMemberCount >= 2;
    }

    /**
     * Ensure the stable can be split, throwing an exception if not.
     *
     * Validates that the stable is in a valid state for splitting into two
     * separate viable stables while maintaining competitive integrity.
     *
     * @throws CannotBeSplitException When splitting is not allowed
     */
    public function ensureCanBeSplit(): void
    {
        if ($this->isRetired()) {
            throw CannotBeSplitException::retired($this);
        }

        if (! $this->isCurrentlyActive()) {
            throw CannotBeSplitException::notActive($this);
        }

        $currentMemberCount = $this->currentWrestlers->count() + $this->currentTagTeams->count();
        if ($currentMemberCount < 2) {
            throw CannotBeSplitException::insufficientMembers($this, $currentMemberCount, 2);
        }

        // Additional business rule validations could be added here:
        // - Active championship reigns by members
        // - Ongoing storylines or feuds
        // - Upcoming major events
        // - Member compatibility checks
    }

    /**
     * Determine if this stable can be merged with another stable.
     *
     * Checks business rules for stable merging:
     * - Stables must not be the same
     * - Both stables must not be retired
     * - Both stables must be currently active
     *
     * @param  Stable  $otherStable  The stable to potentially merge with
     * @return bool True if stables can be merged, false otherwise
     */
    public function canBeMergedWith(Stable $otherStable): bool
    {
        return $this->id !== $otherStable->id
            && ! $this->isRetired()
            && ! $otherStable->isRetired()
            && $this->isCurrentlyActive()
            && $otherStable->isCurrentlyActive();
    }

    /**
     * Ensure this stable can be merged with another, throwing an exception if not.
     *
     * Validates that both stables are in compatible states for merging while
     * maintaining storyline continuity and competitive integrity.
     *
     * @param  Stable  $otherStable  The stable to merge with
     * @throws CannotBeMergedException When merging is not allowed
     */
    public function ensureCanBeMergedWith(Stable $otherStable): void
    {
        if ($this->id === $otherStable->id) {
            throw CannotBeMergedException::selfMerge($this);
        }

        if ($this->isRetired()) {
            throw CannotBeMergedException::primaryRetired($this);
        }

        if ($otherStable->isRetired()) {
            throw CannotBeMergedException::secondaryRetired($otherStable);
        }

        if (! $this->isCurrentlyActive()) {
            throw CannotBeMergedException::primaryNotActive($this);
        }

        if (! $otherStable->isCurrentlyActive()) {
            throw CannotBeMergedException::secondaryNotActive($otherStable);
        }

        // Additional business rule validations could be added here:
        // - Conflicting storylines between stables
        // - Incompatible member relationships
        // - Conflicting championship reigns
        // - Active feuds between stables
        // - Upcoming major events
    }

    /**
     * Determine if the stable can be restored from soft deletion.
     *
     * Checks business rules for stable restoration:
     * - Must be soft deleted (trashed)
     * - Name must not conflict with existing active stables
     * - Should have available former members for viable restoration
     *
     * @return bool True if the stable can be restored, false otherwise
     */
    public function canBeRestored(): bool
    {
        if (! $this->trashed()) {
            return false;
        }

        // Check for name conflicts with existing active stables
        $nameConflict = static::where('name', $this->name)
            ->whereNull('deleted_at')
            ->exists();

        if ($nameConflict) {
            return false;
        }

        // Basic restoration is possible if no conflicts
        return true;
    }

    /**
     * Ensure the stable can be restored from soft deletion, throwing an exception if not.
     *
     * Validates that the stable is in a valid state for restoration while checking
     * for business rule violations and member availability for reunion storylines.
     *
     * @param  bool  $requireFormerMembers  Whether to require available former members
     * @throws CannotBeRestoredException When restoration is not allowed
     */
    public function ensureCanBeRestored(bool $requireFormerMembers = true): void
    {
        if (! $this->trashed()) {
            throw CannotBeRestoredException::notDeleted($this);
        }

        // Check for name conflicts with existing active stables
        $conflictingStable = static::where('name', $this->name)
            ->whereNull('deleted_at')
            ->first();

        if ($conflictingStable) {
            throw CannotBeRestoredException::nameConflict($this, $conflictingStable->name);
        }

        if ($requireFormerMembers) {
            // Check if former members are available for restoration
            $availableFormerMembers = $this->getAvailableFormerMembers();

            if ($availableFormerMembers->isEmpty()) {
                throw CannotBeRestoredException::noAvailableFormerMembers($this);
            }

            // Check minimum member count for viable restoration
            $minimumMembers = static::MIN_MEMBERS_COUNT ?? 2;
            if ($availableFormerMembers->count() < $minimumMembers) {
                throw CannotBeRestoredException::insufficientFormerMembers(
                    $this,
                    $availableFormerMembers->count(),
                    $minimumMembers
                );
            }

            // Check if key former members are available
            $unavailableKeyMembers = $this->getUnavailableKeyFormerMembers();
            if ($unavailableKeyMembers->isNotEmpty()) {
                $memberNames = $unavailableKeyMembers->pluck('name')->join(', ');
                throw CannotBeRestoredException::keyMembersUnavailable($this, $memberNames);
            }
        }

        // Additional business rule validations could be added here:
        // - Storyline conflicts with current active stables
        // - Administrative authorization requirements
        // - Event timing conflicts
        // - Member commitment conflicts
    }

    /**
     * Check if the stable is disbanded.
     *
     * A stable is considered disbanded if it has activity periods but is currently inactive.
     *
     * @return bool True if the stable is disbanded, false otherwise
     */
    public function isDisbanded(): bool
    {
        return $this->hasActivityPeriods() && $this->isInactive();
    }

    /**
     * Get former members who are currently available for reunion.
     *
     * Returns wrestlers and tag teams who were previously in this stable
     * and are currently available (employed and not injured/suspended).
     *
     * @return Collection Collection of available former members
     */
    public function getAvailableFormerMembers(): Collection
    {
        $availableFormerWrestlers = $this->previousWrestlers()
            ->whereHas('employments', function ($query) {
                $query->whereNull('ended_at'); // Currently employed
            })
            ->whereDoesntHave('injuries', function ($query) {
                $query->whereNull('ended_at'); // Not currently injured
            })
            ->whereDoesntHave('suspensions', function ($query) {
                $query->whereNull('ended_at'); // Not currently suspended
            })
            ->get();

        $availableFormerTagTeams = $this->previousTagTeams()
            ->whereHas('employments', function ($query) {
                $query->whereNull('ended_at'); // Currently employed
            })
            ->whereDoesntHave('suspensions', function ($query) {
                $query->whereNull('ended_at'); // Not currently suspended
            })
            ->get();

        return $availableFormerWrestlers->concat($availableFormerTagTeams);
    }

    /**
     * Get key former members who are unavailable for reunion.
     *
     * Returns former members who are considered "key" to the stable's identity
     * but are currently unavailable due to retirement, injury, suspension, or
     * employment with other stables.
     *
     * @return Collection Collection of unavailable key members
     */
    public function getUnavailableKeyFormerMembers(): Collection
    {
        // For now, consider all former members as "key" members
        // This could be enhanced to identify specific key members based on:
        // - Leadership roles, championship history, or storyline importance

        $unavailableFormerWrestlers = $this->previousWrestlers()
            ->where(function ($query) {
                $query->whereHas('retirements', function ($retirementQuery) {
                    $retirementQuery->whereNull('ended_at'); // Currently retired
                })
                    ->orWhereHas('injuries', function ($injuryQuery) {
                        $injuryQuery->whereNull('ended_at'); // Currently injured
                    })
                    ->orWhereHas('suspensions', function ($suspensionQuery) {
                        $suspensionQuery->whereNull('ended_at'); // Currently suspended
                    })
                    ->orWhereHas('currentStables', function ($stableQuery) {
                        $stableQuery->where('stable_id', '!=', $this->id); // In another stable
                    });
            })
            ->get();

        $unavailableFormerTagTeams = $this->previousTagTeams()
            ->where(function ($query) {
                $query->whereHas('retirements', function ($retirementQuery) {
                    $retirementQuery->whereNull('ended_at'); // Currently retired
                })
                    ->orWhereHas('suspensions', function ($suspensionQuery) {
                        $suspensionQuery->whereNull('ended_at'); // Currently suspended
                    })
                    ->orWhereHas('currentStables', function ($stableQuery) {
                        $stableQuery->where('stable_id', '!=', $this->id); // In another stable
                    });
            })
            ->get();

        return $unavailableFormerWrestlers->concat($unavailableFormerTagTeams);
    }
}
