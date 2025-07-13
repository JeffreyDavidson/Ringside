<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Status\CannotBeEmployedException;
use App\Exceptions\Status\CannotBeReleasedException;

/**
 * Provides employment validation functionality for models.
 *
 * This trait adds validation methods for employment state transitions.
 * It should be used alongside the IsEmployable trait on models that can be employed.
 * Models using this trait must also implement retirement functionality.
 *
 * @see IsEmployable For core employment functionality
 *
 * @phpstan-require-implements \App\Models\Contracts\Retirable
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Employable, Retirable
 * {
 *     use IsEmployable, IsRetirable, ValidatesEmployment;
 * }
 *
 * // Usage:
 * $wrestler = Wrestler::find(1);
 * $wrestler->ensureCanBeEmployed();    // Throws exception if cannot employ
 * $wrestler->canBeEmployed();          // Returns boolean
 * ```
 */
trait ValidatesEmployment
{
    /**
     * Check if the model is retired.
     *
     * This method must be implemented by models using this trait.
     * Typically provided by the IsRetirable trait.
     */
    abstract public function isRetired(): bool;

    /**
     * Determine if the model can be employed.
     *
     * Checks business rules to determine if employment is allowed:
     * - Must not already be employed
     * - Must not be currently retired
     *
     * @return bool True if the model can be employed, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeEmployed()) {
     *     // Perform employment logic
     * }
     * ```
     */
    public function canBeEmployed(): bool
    {
        // Cannot employ if already employed
        if ($this->isEmployed()) {
            return false;
        }

        // Cannot employ if currently retired (need to end retirement first)
        if ($this->isRetired()) {
            return false;
        }

        return true;
    }

    /**
     * Ensure the model can be employed, throwing an exception if not.
     *
     * @throws CannotBeEmployedException When employment is not allowed
     *
     * @example
     * ```php
     * try {
     *     $wrestler->ensureCanBeEmployed();
     *     // Proceed with employment
     * } catch (CannotBeEmployedException $e) {
     *     // Handle employment validation failure
     * }
     * ```
     */
    public function ensureCanBeEmployed(): void
    {
        if ($this->isEmployed()) {
            throw CannotBeEmployedException::employed();
        }
        if (method_exists($this, 'hasFutureEmployment') && $this->hasFutureEmployment()) {
            throw CannotBeEmployedException::employed();
        }
    }

    /**
     * Determine if the model can be released from employment.
     *
     * Checks business rules for release:
     * - Must not be unemployed (already not employed)
     * - Must not have future employment (would conflict)
     * - Must not be retired
     * - Must not already be released
     *
     * @return bool True if the model can be released, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeReleased()) {
     *     // Perform release logic
     * }
     * ```
     */
    public function canBeReleased(): bool
    {
        return ! $this->isNotInEmployment()
            && ! $this->hasFutureEmployment()
            && ! $this->isRetired();
    }

    /**
     * Ensure the model can be released, throwing an exception if not.
     *
     * @throws CannotBeReleasedException When release is not allowed
     *
     * @example
     * ```php
     * try {
     *     $wrestler->ensureCanBeReleased();
     *     // Proceed with release
     * } catch (CannotBeReleasedException $e) {
     *     // Handle release validation failure
     * }
     * ```
     */
    public function ensureCanBeReleased(): void
    {
        if ($this->isNotInEmployment()) {
            throw CannotBeReleasedException::unemployed();
        }

        if ($this->hasFutureEmployment()) {
            throw CannotBeReleasedException::hasFutureEmployment();
        }

        if ($this->isRetired()) {
            throw CannotBeReleasedException::retired();
        }
    }
}
