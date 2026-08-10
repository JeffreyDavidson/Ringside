<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeRetiredException;
use App\Models\TagTeams\TagTeam;

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
class TagTeamRetirementValidation
{
    /**
     * Validate that a TagTeam can be retired.
     *
     * Performs TagTeam-specific retirement validation:
     * - Standard TagTeam employment checks
     * - Validates all current wrestlers can be retired
     * - Ensures no wrestlers are injured or suspended
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function validate(TagTeam $tagTeam): void
    {
        $this->validateTagTeamStatus($tagTeam);
        $this->validateCurrentWrestlers($tagTeam);
    }

    /**
     * Validate the TagTeam's own status for retirement.
     *
     * @throws CannotBeRetiredException When TagTeam status prevents retirement
     */
    private function validateTagTeamStatus(TagTeam $tagTeam): void
    {
        if ($this->isUnemployed($tagTeam)) {
            throw CannotBeRetiredException::unemployed($tagTeam);
        }

        if ($tagTeam->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment($tagTeam);
        }

        if ($tagTeam->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($tagTeam);
        }
    }

    /**
     * Validate that all current wrestlers can be retired.
     *
     * This is the complex TagTeam-specific logic that checks each wrestler's
     * status to ensure the entire tag team can be retired properly.
     *
     * @throws CannotBeRetiredException When wrestlers prevent TagTeam retirement
     */
    private function validateCurrentWrestlers(TagTeam $tagTeam): void
    {
        $currentWrestlers = $tagTeam->currentWrestlers()->get();

        if ($currentWrestlers->isEmpty()) {
            throw CannotBeRetiredException::noActiveWrestlers($tagTeam);
        }

        foreach ($currentWrestlers as $wrestler) {
            if ($wrestler->isInjured()) {
                throw CannotBeRetiredException::wrestlerInjured($tagTeam, $wrestler);
            }

            if ($wrestler->isSuspended()) {
                throw CannotBeRetiredException::wrestlerSuspended($tagTeam, $wrestler);
            }

            if (! $wrestler->canBeRetired()) {
                throw CannotBeRetiredException::wrestlerCannotBeRetired($tagTeam, $wrestler);
            }
        }
    }

    /**
     * Check if the TagTeam is unemployed.
     */
    private function isUnemployed(TagTeam $tagTeam): bool
    {
        return $tagTeam->hasStatus(EmploymentStatus::Unemployed);
    }
}
