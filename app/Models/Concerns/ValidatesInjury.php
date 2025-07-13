<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Exceptions\Status\CannotBeInjuredException;

/**
 * Provides injury validation functionality for models.
 *
 * This trait adds validation methods for injury state transitions.
 * It should be used alongside the IsInjurable trait on models that can be injured.
 *
 * @see IsInjurable For core injury functionality
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Injurable
 * {
 *     use IsInjurable, ValidatesInjury;
 * }
 *
 * // Usage:
 * $wrestler = Wrestler::find(1);
 * $wrestler->ensureCanBeInjured();    // Throws exception if cannot injure
 * $wrestler->canBeInjured();          // Returns boolean
 * ```
 */
trait ValidatesInjury
{
    /**
     * Determine if the model can be injured.
     *
     * Checks all business rules to determine if injury is allowed:
     * - Must not be unemployed
     * - Must not be released
     * - Must not be retired
     * - Must not have future employment scheduled
     * - Must not already be injured
     * - Must not be suspended
     *
     * @return bool True if the model can be injured, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeInjured()) {
     *     // Perform injury logic
     * }
     * ```
     */
    public function canBeInjured(): bool
    {
        return ! $this->isUnemployed()
            && ! $this->isReleased()
            && ! $this->isRetired()
            && ! $this->hasFutureEmployment()
            && ! $this->isInjured()
            && ! $this->isSuspended();
    }

    /**
     * Ensure the model can be injured, throwing an exception if not.
     *
     * Validates all business rules and throws specific exceptions for different
     * failure scenarios. This provides clear feedback about why injury failed.
     *
     * @throws CannotBeInjuredException When injury is not allowed
     *
     * @example
     * ```php
     * try {
     *     $wrestler->ensureCanBeInjured();
     *     // Proceed with injury
     * } catch (CannotBeInjuredException $e) {
     *     // Handle specific injury validation failure
     * }
     * ```
     */
    public function ensureCanBeInjured(): void
    {
        if ($this->isUnemployed()) {
            throw CannotBeInjuredException::unemployed();
        }

        if ($this->isReleased()) {
            throw CannotBeInjuredException::released();
        }

        if ($this->isRetired()) {
            throw CannotBeInjuredException::retired();
        }

        if ($this->hasFutureEmployment()) {
            throw CannotBeInjuredException::hasFutureEmployment();
        }

        if ($this->isInjured()) {
            throw CannotBeInjuredException::injured();
        }

        if ($this->isSuspended()) {
            throw CannotBeInjuredException::suspended();
        }
    }

    /**
     * Determine if the model can be cleared from injury.
     *
     * Simple check to see if the model is currently injured, which is the
     * only requirement for being able to clear the injury.
     *
     * @return bool True if the model can be cleared from injury, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeClearedFromInjury()) {
     *     // Perform injury clearance logic
     * }
     * ```
     */
    public function canBeClearedFromInjury(): bool
    {
        return $this->isInjured();
    }

    /**
     * Ensure the model can be cleared from injury, throwing an exception if not.
     *
     * @throws CannotBeClearedFromInjuryException When injury clearance is not allowed
     *
     * @example
     * ```php
     * try {
     *     $wrestler->ensureCanBeClearedFromInjury();
     *     // Proceed with injury clearance
     * } catch (CannotBeClearedFromInjuryException $e) {
     *     // Handle injury clearance validation failure
     * }
     * ```
     */
    public function ensureCanBeClearedFromInjury(): void
    {
        if (! $this->isInjured()) {
            throw CannotBeClearedFromInjuryException::notInjured();
        }
    }

    /**
     * Determine if the model can be healed from injury.
     *
     * Alias for canBeClearedFromInjury() with better semantic naming.
     *
     * @return bool True if the model can be healed from injury, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeHealed()) {
     *     // Perform healing logic
     * }
     * ```
     */
    public function canBeHealed(): bool
    {
        return $this->canBeClearedFromInjury();
    }

    /**
     * Ensure the model can be healed from injury, throwing an exception if not.
     *
     * @throws CannotBeClearedFromInjuryException When healing is not allowed
     *
     * @example
     * ```php
     * try {
     *     $wrestler->ensureCanBeHealed();
     *     // Proceed with healing
     * } catch (CannotBeClearedFromInjuryException $e) {
     *     // Handle healing validation failure
     * }
     * ```
     */
    public function ensureCanBeHealed(): void
    {
        $this->ensureCanBeClearedFromInjury();
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
