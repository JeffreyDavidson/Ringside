<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Titles\CannotBeDebutedException;
use App\Exceptions\Titles\CannotBePulledException;
use App\Exceptions\Titles\CannotBeReinstatedException;

/**
 * Provides title lifecycle validation functionality for Title models.
 *
 * This trait adds validation methods for title-specific lifecycle transitions including
 * debut (first-time activation), reinstatement, and pulling from competition.
 *
 * @see HasActivityPeriods For core activation functionality
 *
 * @example
 * ```php
 * class Title extends Model
 * {
 *     use HasActivityPeriods, ValidatesTitleLifecycle;
 * }
 *
 * // Usage:
 * $title = Title::find(1);
 * $title->ensureCanBeDebuted();      // For first-time activation
 * $title->ensureCanBeReinstated();   // For reactivation
 * $title->canBeDebuted();            // Returns boolean for debut
 * $title->canBePulled();             // Returns boolean for pulling
 * ```
 */
trait ValidatesTitleLifecycle
{
    /**
     * Determine if the title can be debuted (first-time activation).
     *
     * Checks business rules to determine if debut is allowed:
     * - Must not have any previous activity periods (never been active)
     * - Must not be retired
     *
     * @return bool True if the title can be debuted, false otherwise
     */
    public function canBeDebuted(): bool
    {
        return ! $this->hasActivityPeriods() && ! $this->isRetired();
    }

    /**
     * Ensure the title can be debuted, throwing an exception if not.
     *
     * @throws CannotBeDebutedException When debut is not allowed
     */
    public function ensureCanBeDebuted(): void
    {
        if ($this->hasActivityPeriods()) {
            throw CannotBeDebutedException::alreadyDebuted($this);
        }

        if ($this->isRetired()) {
            throw CannotBeDebutedException::retired($this);
        }
    }

    /**
     * Determine if the title can be reinstated/reactivated.
     *
     * Checks business rules to determine if reactivation is allowed:
     * - Must have previous activity periods (has been active before)
     * - Must not currently be active
     * - Must not be retired
     *
     * @return bool True if the title can be reinstated, false otherwise
     */
    public function canBeReinstated(): bool
    {
        return $this->hasActivityPeriods()
            && ! $this->isCurrentlyActive()
            && ! $this->isRetired();
    }

    /**
     * Ensure the title can be reinstated, throwing an exception if not.
     *
     * @throws CannotBeReinstatedException When reinstatement is not allowed
     */
    public function ensureCanBeReinstated(): void
    {
        if (! $this->hasActivityPeriods()) {
            throw CannotBeReinstatedException::neverActivated($this);
        }

        if ($this->isCurrentlyActive()) {
            throw CannotBeReinstatedException::active($this);
        }

        if ($this->isRetired()) {
            throw CannotBeReinstatedException::retired($this);
        }
    }

    /**
     * Check if the title can be pulled from competition.
     *
     * A title can be pulled if it's currently active and not retired.
     *
     * @return bool True if the title can be pulled, false otherwise
     */
    public function canBePulled(): bool
    {
        return $this->isCurrentlyActive() && ! $this->isRetired();
    }

    /**
     * Ensure the title can be pulled from competition.
     *
     * @throws CannotBePulledException When pulling is not allowed
     */
    public function ensureCanBePulled(): void
    {
        if (! $this->isCurrentlyActive()) {
            throw CannotBePulledException::notActive($this);
        }

        if ($this->isRetired()) {
            throw CannotBePulledException::retired($this);
        }
    }
}
