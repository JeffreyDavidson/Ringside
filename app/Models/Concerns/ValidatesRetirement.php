<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\RosterMemberType;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Contracts\RetirementValidationStrategy;
use App\Models\TagTeams\TagTeam;
use App\Models\Validation\Strategies\IndividualRetirementValidation;
use App\Models\Validation\Strategies\TagTeamRetirementValidation;
use Exception;

/**
 * Provides retirement validation functionality for models.
 *
 * This trait adds validation methods for retirement state transitions.
 * It should be used alongside the IsRetirable trait on models that can be retired.
 *
 * @see IsRetirable For core retirement functionality
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Retirable
 * {
 *     use IsRetirable, ValidatesRetirement;
 * }
 *
 * // Usage:
 * $wrestler = Wrestler::find(1);
 * $wrestler->ensureCanBeRetired();    // Throws exception if cannot retire
 * $wrestler->canBeRetired();          // Returns boolean
 * ```
 */
trait ValidatesRetirement
{
    /**
     * Determine if the model can be retired.
     *
     * Uses the appropriate validation strategy based on the entity type
     * to check if retirement is allowed.
     *
     * @return bool True if the model can be retired, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeRetired()) {
     *     // Perform retirement logic
     * }
     * ```
     */
    public function canBeRetired(): bool
    {
        try {
            $this->ensureCanBeRetired();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ensure the model can be retired, throwing an exception if not.
     *
     * Uses the Strategy pattern to delegate validation to the appropriate
     * strategy based on the entity type (individual vs TagTeam).
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     *
     * @example
     * ```php
     * try {
     *     $wrestler->ensureCanBeRetired();
     *     // Proceed with retirement
     * } catch (CannotBeRetiredException $e) {
     *     // Handle specific retirement validation failure
     * }
     * ```
     */
    public function ensureCanBeRetired(): void
    {
        app($this->getRetirementValidationStrategy())->validate($this);
    }

    /**
     * Determine if the model can be unretired (come out of retirement).
     *
     * Simple check to see if the model is currently retired, which is the
     * only requirement for being able to unretire.
     *
     * @return bool True if the model can be unretired, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeUnretired()) {
     *     // Perform unretirement logic
     * }
     * ```
     */
    public function canBeUnretired(): bool
    {
        return $this->isRetired();
    }

    /**
     * Ensure the model can be unretired, throwing an exception if not.
     *
     * @throws CannotBeUnretiredException When unretirement is not allowed
     *
     * @example
     * ```php
     * try {
     *     $wrestler->ensureCanBeUnretired();
     *     // Proceed with unretirement
     * } catch (CannotBeUnretiredException $e) {
     *     // Handle unretirement validation failure
     * }
     * ```
     */
    public function ensureCanBeUnretired(): void
    {
        if (! $this->isRetired()) {
            throw CannotBeUnretiredException::notRetired();
        }
    }

    /**
     * Get the appropriate retirement validation strategy for this entity.
     *
     * Determines which validation strategy to use based on the entity type.
     * TagTeams require complex validation of their wrestlers, while individual
     * entities use standard validation.
     *
     * @return class-string<RetirementValidationStrategy> The strategy class name
     *
     * @example
     * ```php
     * // For individual entities (Wrestler, Manager, Referee)
     * $strategy = $wrestler->getRetirementValidationStrategy();
     * // Returns: IndividualRetirementValidation::class
     *
     * // For TagTeam entities
     * $strategy = $tagTeam->getRetirementValidationStrategy();
     * // Returns: TagTeamRetirementValidation::class
     * ```
     */
    protected function getRetirementValidationStrategy(): string
    {
        return RosterMemberType::getValidationStrategy($this, 'retirement');
    }
}
