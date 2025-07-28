<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
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
 * $stable->canBeEstablished();        // Returns boolean for establishment
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
