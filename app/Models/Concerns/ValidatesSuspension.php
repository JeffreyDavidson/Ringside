<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\RosterMemberType;
use App\Exceptions\Roster\CannotBeReinstatedException;
use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Suspendable;
use App\Models\Contracts\SuspensionValidationStrategy;
use App\Models\TagTeams\TagTeam;
use Exception;

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
        app($this->getSuspensionValidationStrategy())->validate($this);
    }

    /**
     * Determine if the model can be reinstated (unsuspended).
     *
     * Checks business rules for reinstatement:
     * - Must not be unemployed
     * - Must not be released
     * - Must not have future employment
     * - Must not be retired
     * - Must be suspended or injured
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
        $type = RosterMemberType::fromModel($this);
        $isSuspended = $this instanceof Suspendable && $this->isSuspended();
        $isInjured = $type->canBeInjured() && $this instanceof Injurable && $this->isInjured();

        return ! $this->isNotInEmployment()
            && ! $this->isReleased()
            && ! $this->hasFutureEmployment()
            && ! $this->isRetired()
            && ($isSuspended || $isInjured);
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
        $type = RosterMemberType::fromModel($this);
        $isSuspended = $this instanceof Suspendable && $this->isSuspended();
        $isInjured = $type->canBeInjured() && $this instanceof Injurable && $this->isInjured();

        if (! $isSuspended && ! $isInjured) {
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

    /**
     * Get the appropriate suspension validation strategy for this entity.
     *
     * Determines which validation strategy to use based on the entity type.
     * TagTeams require complex validation of their wrestlers, while individual
     * entities use standard validation.
     *
     * @return class-string<SuspensionValidationStrategy> The strategy class name
     */
    protected function getSuspensionValidationStrategy(): string
    {
        return RosterMemberType::getValidationStrategy($this, 'suspension');
    }
}
