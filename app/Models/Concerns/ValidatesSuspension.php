<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Enums\Shared\RosterMemberType;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\Contracts\Bookable;
use App\Models\Contracts\Injurable;
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
     * - Must not be injured
     * - Must not be retired
     * - Must not be bookable (already reinstated)
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

        return ! $this->isUnemployed()
            && ! $this->isReleased()
            && ! $this->hasFutureEmployment()
            && (! $type->canBeInjured() || ! ($this instanceof Injurable) || ! $this->isInjured())
            && ! $this->isRetired()
            && (! ($this instanceof Bookable) || ! $this->isBookable());
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
        if ($this->isUnemployed()) {
            throw CannotBeReinstatedException::unemployed();
        }

        if ($this->isReleased()) {
            throw CannotBeReinstatedException::released();
        }

        if ($this->hasFutureEmployment()) {
            throw CannotBeReinstatedException::hasFutureEmployment();
        }

        $type = RosterMemberType::fromModel($this);
        if ($type->canBeInjured() && ($this instanceof Injurable) && $this->isInjured()) {
            throw CannotBeReinstatedException::injured();
        }

        if ($this->isRetired()) {
            throw CannotBeReinstatedException::retired();
        }

        if ($this instanceof Bookable && $this->isBookable()) {
            throw CannotBeReinstatedException::bookable();
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

    /**
     * Check if the model is unemployed.
     *
     * @return bool True if unemployed, false otherwise
     */
    private function isUnemployed(): bool
    {
        return $this->hasStatus(EmploymentStatus::Unemployed);
    }
}
