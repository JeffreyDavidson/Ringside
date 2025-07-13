<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Status\CannotBeActivatedException;
use App\Exceptions\Status\CannotBeDeactivatedException;
use App\Exceptions\Status\CannotBeDisbandedException;
use App\Exceptions\Status\CannotBeDebutedException;

/**
 * Provides comprehensive activation validation functionality for models.
 *
 * This trait adds validation methods for all activation state transitions including
 * debut (first-time activation), reactivation, and deactivation. It should be used
 * alongside the HasActivityPeriods trait on models that can be activated/deactivated.
 *
 * @see HasActivityPeriods For core activation functionality
 *
 * @example
 * ```php
 * class Title extends Model
 * {
 *     use HasActivityPeriods, ValidatesActivation;
 * }
 *
 * // Usage:
 * $title = Title::find(1);
 * $title->ensureCanBeDebuted();      // For first-time activation
 * $title->ensureCanBeReinstated();   // For reactivation
 * $title->ensureCanBeDeactivated();  // For deactivation
 * $title->canBeDebuted();            // Returns boolean for debut
 * ```
 */
trait ValidatesActivation
{
    /**
     * Determine if the model can be activated (generic activation check).
     *
     * @deprecated Use canBeDebuted() or canBeReinstated() for specific use cases
     *
     * Checks business rules to determine if activation is allowed:
     * - Must not already be activated
     *
     * @return bool True if the model can be activated, false otherwise
     */
    public function canBeActivated(): bool
    {
        return ! $this->isCurrentlyActive();
    }

    /**
     * Ensure the model can be activated, throwing an exception if not.
     *
     * @deprecated Use ensureCanBeDebuted() or ensureCanBeReinstated() for specific use cases
     *
     * @throws CannotBeActivatedException When activation is not allowed
     */
    public function ensureCanBeActivated(): void
    {
        if ($this->isCurrentlyActive()) {
            throw CannotBeActivatedException::alreadyActivated();
        }
    }

    /**
     * Determine if the model can be deactivated.
     *
     * Checks business rules for deactivation:
     * - Must not be unactivated (never been activated)
     * - Must not already be deactivated
     * - Must not have future activation
     * - Must not be retired
     *
     * @return bool True if the model can be deactivated, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->canBeDeactivated()) {
     *     // Perform deactivation logic
     * }
     * ```
     */
    public function canBeDeactivated(): bool
    {
        return $this->hasActivityPeriods()
            && $this->isCurrentlyActive()
            && ! $this->hasFutureActivation()
            && ! $this->isRetired();
    }

    /**
     * Ensure the model can be deactivated, throwing an exception if not.
     *
     * @throws CannotBeDeactivatedException When deactivation is not allowed
     *
     * @example
     * ```php
     * try {
     *     $title->ensureCanBeDeactivated();
     *     // Proceed with deactivation
     * } catch (CannotBeDeactivatedException $e) {
     *     // Handle deactivation validation failure
     * }
     * ```
     */
    public function ensureCanBeDeactivated(): void
    {
        if (! $this->hasActivityPeriods()) {
            throw CannotBeDeactivatedException::unactivated();
        }

        if (! $this->isCurrentlyActive()) {
            throw CannotBeDeactivatedException::alreadyDeactivated();
        }

        if ($this->hasFutureActivation()) {
            throw CannotBeDeactivatedException::hasFutureActivation();
        }

        if ($this->isRetired()) {
            throw CannotBeDeactivatedException::retired();
        }
    }

    /**
     * Determine if the model can be debuted (first-time activation).
     *
     * Checks business rules to determine if debut is allowed:
     * - Must not have any previous activity periods (never been active)
     * - Must not be retired
     *
     * @return bool True if the model can be debuted, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->canBeDebuted()) {
     *     // Perform debut logic
     * }
     * ```
     */
    public function canBeDebuted(): bool
    {
        return ! $this->hasActivityPeriods() && ! $this->isRetired();
    }

    /**
     * Ensure the model can be debuted, throwing an exception if not.
     *
     * @throws CannotBeDebutedException When debut is not allowed
     *
     * @example
     * ```php
     * try {
     *     $title->ensureCanBeDebuted();
     *     // Proceed with debut
     * } catch (CannotBeDebutedException $e) {
     *     // Handle debut validation failure
     * }
     * ```
     */
    public function ensureCanBeDebuted(): void
    {
        if ($this->hasActivityPeriods()) {
            throw CannotBeDebutedException::alreadyDebuted();
        }

        if ($this->isRetired()) {
            throw CannotBeDebutedException::retired();
        }
    }

    /**
     * Determine if the model can be reinstated/reactivated.
     *
     * Checks business rules to determine if reactivation is allowed:
     * - Must have previous activity periods (has been active before)
     * - Must not currently be active
     * - Must not be retired
     *
     * @return bool True if the model can be reinstated, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->canBeReinstated()) {
     *     // Perform reinstatement logic
     * }
     * ```
     */
    public function canBeReinstated(): bool
    {
        return $this->hasActivityPeriods()
            && ! $this->isCurrentlyActive()
            && ! $this->isRetired();
    }

    /**
     * Ensure the model can be reinstated, throwing an exception if not.
     *
     * @throws CannotBeActivatedException When reinstatement is not allowed
     *
     * @example
     * ```php
     * try {
     *     $title->ensureCanBeReinstated();
     *     // Proceed with reinstatement
     * } catch (CannotBeActivatedException $e) {
     *     // Handle reinstatement validation failure
     * }
     * ```
     */
    public function ensureCanBeReinstated(): void
    {
        if (! $this->hasActivityPeriods()) {
            throw CannotBeActivatedException::neverActivated();
        }

        if ($this->isCurrentlyActive()) {
            throw CannotBeActivatedException::activated();
        }

        if ($this->isRetired()) {
            throw CannotBeActivatedException::retired();
        }
    }

    /**
     * Check if the title can be pulled from competition.
     *
     * A title can be pulled if it's currently active and not retired.
     *
     * @return bool True if the title can be pulled, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * if ($title->canBePulled()) {
     *     PullAction::run($title);
     * }
     * ```
     */
    public function canBePulled(): bool
    {
        return $this->isCurrentlyActive() && ! $this->isRetired();
    }

    /**
     * Check if the entity can be disbanded.
     *
     * Alias for ensureCanBeDeactivated() to provide domain-specific language
     * for stable-related operations.
     *
     * @throws CannotBeDisbandedException When entity cannot be disbanded
     */
    public function ensureCanBeDisbanded(): void
    {
        if (! $this->hasActivityPeriods()) {
            throw CannotBeDisbandedException::unactivated();
        }

        if (! $this->isCurrentlyActive()) {
            throw CannotBeDisbandedException::disbanded();
        }

        if ($this->hasFutureActivation()) {
            throw CannotBeDisbandedException::hasFutureActivation();
        }

        if ($this->isRetired()) {
            throw CannotBeDisbandedException::retired();
        }
    }

    /**
     * Check if the entity is disbanded.
     *
     * An entity is considered disbanded if it has activity periods but is currently inactive.
     *
     * @return bool True if the entity is disbanded, false otherwise
     */
    public function isDisbanded(): bool
    {
        return $this->hasActivityPeriods() && $this->isInactive();
    }
}
