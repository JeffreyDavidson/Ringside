<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Suspendable;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Validation\Strategies\IndividualSuspensionValidation;
use App\Models\Wrestlers\Wrestler;
use Exception;
use LogicException;

/**
 * Provides suspension validation functionality for models.
 *
 * This trait adds validation methods for suspension state transitions.
 * It should be used alongside the IsSuspendable trait on models that can be suspended.
 *
 * @see IsSuspendable For core suspension functionality
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Suspendable
 * {
 *     use IsSuspendable, ValidatesSuspension;
 * }
 *
 * // Usage:
 * $wrestler = Wrestler::find(1);
 * $wrestler->ensureCanBeSuspended();    // Throws exception if cannot suspend
 * $wrestler->canBeSuspended();          // Returns boolean
 * ```
 */
trait ValidatesSuspension
{
    /**
     * Determine if the model can be suspended.
     *
     * Uses the appropriate validation strategy based on the entity type
     * to check if suspension is allowed.
     *
     * @return bool True if the model can be suspended, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeSuspended()) {
     *     // Perform suspension logic
     * }
     * ```
     */
    public function canBeSuspended(): bool
    {
        try {
            $this->ensureCanBeSuspended();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ensure the model can be suspended, throwing an exception if not.
     *
     * Uses the Strategy pattern to delegate validation to the appropriate
     * strategy based on the entity type (individual vs TagTeam).
     *
     * @throws CannotBeSuspendedException When suspension is not allowed
     *
     * @example
     * ```php
     * try {
     *     $wrestler->ensureCanBeSuspended();
     *     // Proceed with suspension
     * } catch (CannotBeSuspendedException $e) {
     *     // Handle specific suspension validation failure
     * }
     * ```
     */
    public function ensureCanBeSuspended(): void
    {
        if ($this instanceof Wrestler || $this instanceof Manager || $this instanceof Referee) {
            (new IndividualSuspensionValidation())->validate($this);

            return;
        }

        throw new LogicException(sprintf('%s does not support shared suspension validation.', static::class));
    }

    /**
     * Determine if the model can be reinstated (unsuspended).
     *
     * Checks business rules for reinstatement:
     * - Must not be unemployed
     * - Must not be released
     * - Must not have future employment
     * - Must not be retired
     * - Must not be bookable
     * - Must be suspended
     * - Injured roster members must be healed instead
     *
     * @return bool True if the model can be reinstated, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeReinstated()) {
     *     // Perform reinstatement logic
     * }
     * ```
     */
    public function canBeReinstated(): bool
    {
        $isSuspended = $this instanceof Suspendable && $this->isSuspended();
        $isBookable = $this instanceof Bookable && $this->isBookable();

        return ! $this->isNotInEmployment()
            && ! $this->isReleased()
            && ! $this->hasFutureEmployment()
            && ! $this->isRetired()
            && ! $isBookable
            && $isSuspended;
    }

    /**
     * Ensure the model can be reinstated, throwing an exception if not.
     *
     * @throws CannotBeReinstatedException When reinstatement is not allowed
     *
     * @example
     * ```php
     * try {
     *     $wrestler->ensureCanBeReinstated();
     *     // Proceed with reinstatement
     * } catch (CannotBeReinstatedException $e) {
     *     // Handle reinstatement validation failure
     * }
     * ```
     */
    public function ensureCanBeReinstated(): void
    {
        $isSuspended = $this instanceof Suspendable && $this->isSuspended();

        if ($this instanceof Injurable && $this->isInjured()) {
            throw CannotBeReinstatedException::injured($this);
        }

        if (! $isSuspended) {
            throw CannotBeReinstatedException::available($this);
        }

        if ($this->isNotInEmployment()) {
            throw CannotBeReinstatedException::unemployed($this);
        }

        if ($this->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment($this);
        }

        if ($this->isRetired()) {
            throw CannotBeReinstatedException::retired($this);
        }

        if ($this instanceof Bookable && $this->isBookable()) {
            throw CannotBeReinstatedException::bookable($this);
        }
    }
}
