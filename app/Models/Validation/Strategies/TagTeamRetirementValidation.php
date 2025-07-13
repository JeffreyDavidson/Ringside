<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Contracts\RetirementValidationStrategy;
use Illuminate\Database\Eloquent\Model;

/**
 * Retirement validation strategy for TagTeam entities.
 *
 * This strategy handles the complex retirement validation for TagTeams,
 * which includes validating the status of current wrestlers in addition
 * to standard TagTeam validation rules.
 *
 * @example
 * ```php
 * $strategy = new TagTeamRetirementValidation();
 * $strategy->validate($tagTeam);
 * ```
 */
class TagTeamRetirementValidation implements RetirementValidationStrategy
{
    /**
     * Validate that a TagTeam can be retired.
     *
     * Performs TagTeam-specific retirement validation:
     * - Standard TagTeam employment checks
     * - Validates all current wrestlers can be retired
     * - Ensures no wrestlers are injured or suspended
     *
     * @param  Model  $tagTeam  The TagTeam entity to validate
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function validate(Model $tagTeam): void
    {
        // Standard TagTeam validation
        $this->validateTagTeamStatus($tagTeam);

        // Complex validation: check current wrestlers
        $this->validateCurrentWrestlers($tagTeam);
    }

    /**
     * Validate the TagTeam's own status for retirement.
     *
     * @param  Model  $tagTeam  The TagTeam to validate
     *
     * @throws CannotBeRetiredException When TagTeam status prevents retirement
     */
    private function validateTagTeamStatus(Model $tagTeam): void
    {
        if ($this->isUnemployed($tagTeam)) {
            throw CannotBeRetiredException::unemployed();
        }

        if (method_exists($tagTeam, 'hasFutureEmployment') && $tagTeam->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment();
        }

        if (method_exists($tagTeam, 'isRetired') && $tagTeam->isRetired()) {
            throw CannotBeRetiredException::retired();
        }
    }

    /**
     * Validate that all current wrestlers can be retired.
     *
     * This is the complex TagTeam-specific logic that checks each wrestler's
     * status to ensure the entire tag team can be retired properly.
     *
     * @param  Model  $tagTeam  The TagTeam to validate
     *
     * @throws CannotBeRetiredException When wrestlers prevent TagTeam retirement
     */
    private function validateCurrentWrestlers(Model $tagTeam): void
    {
        $currentWrestlers = method_exists($tagTeam, 'currentWrestlers') ? $tagTeam->currentWrestlers()->get() : collect();

        if ($currentWrestlers->isEmpty()) {
            throw CannotBeRetiredException::noActiveWrestlers();
        }

        foreach ($currentWrestlers as $wrestler) {
            // Check if wrestler is in a state that prevents retirement
            if (method_exists($wrestler, 'isInjured') && $wrestler->isInjured()) {
                $name = method_exists($wrestler, 'getAttribute') ? $wrestler->getAttribute('name') ?? 'Unknown wrestler' : 'Unknown wrestler';
                throw CannotBeRetiredException::wrestlerInjured($name);
            }

            if (method_exists($wrestler, 'isSuspended') && $wrestler->isSuspended()) {
                $name = method_exists($wrestler, 'getAttribute') ? $wrestler->getAttribute('name') ?? 'Unknown wrestler' : 'Unknown wrestler';
                throw CannotBeRetiredException::wrestlerSuspended($name);
            }

            // Ensure the wrestler themselves can be retired
            // This prevents cascading retirement issues
            if (method_exists($wrestler, 'canBeRetired') && ! $wrestler->canBeRetired()) {
                $name = method_exists($wrestler, 'getAttribute') ? $wrestler->getAttribute('name') ?? 'Unknown wrestler' : 'Unknown wrestler';
                throw CannotBeRetiredException::wrestlerCannotBeRetired($name);
            }
        }
    }

    /**
     * Check if the TagTeam is unemployed.
     *
     * @param  Model  $tagTeam  The TagTeam to check
     * @return bool True if unemployed, false otherwise
     */
    private function isUnemployed(Model $tagTeam): bool
    {
        return method_exists($tagTeam, 'hasStatus') ? $tagTeam->hasStatus(EmploymentStatus::Unemployed) : false;
    }
}
