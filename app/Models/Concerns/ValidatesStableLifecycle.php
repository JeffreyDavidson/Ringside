<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;

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
}
