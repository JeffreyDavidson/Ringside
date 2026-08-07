<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException;
use App\Models\Contracts\SuspensionValidationStrategy;
use App\Models\TagTeams\TagTeam;
use Illuminate\Database\Eloquent\Model;

/**
 * Suspension validation strategy for TagTeam entities.
 *
 * This strategy handles the complex suspension validation for TagTeams,
 * including validation of current wrestlers.
 */
class TagTeamSuspensionValidation implements SuspensionValidationStrategy
{
    /**
     * Validate that a TagTeam can be suspended.
     *
     * @param  Model  $tagTeam  The TagTeam entity to validate
     * @throws CannotBeSuspendedException When suspension is not allowed
     */
    public function validate(Model $tagTeam): void
    {
        if (! $tagTeam instanceof TagTeam) {
            return;
        }

        // Standard TagTeam validation
        $this->validateTagTeamStatus($tagTeam);

        // TagTeam-specific: validate current wrestlers
        $this->validateCurrentWrestlers($tagTeam);
    }

    /**
     * Validate the TagTeam's own status for suspension.
     *
     * @param  TagTeam  $tagTeam  The TagTeam to validate
     * @throws CannotBeSuspendedException When TagTeam status prevents suspension
     */
    private function validateTagTeamStatus(TagTeam $tagTeam): void
    {
        if ($this->isUnemployed($tagTeam)) {
            throw CannotBeSuspendedException::notEmployed($tagTeam);
        }

        if ($this->isReleased($tagTeam)) {
            throw CannotBeSuspendedException::notEmployed($tagTeam);
        }

        if ($tagTeam->isRetired()) {
            throw CannotBeSuspendedException::notEmployed($tagTeam);
        }

        if ($tagTeam->hasFutureEmployment()) {
            throw CannotBeSuspendedException::notEmployed($tagTeam);
        }

        if ($tagTeam->isSuspended()) {
            throw CannotBeSuspendedException::alreadySuspended($tagTeam);
        }

        if (method_exists($tagTeam, 'isInjured') && $tagTeam->isInjured()) {
            throw CannotBeSuspendedException::requiresAuthorization($tagTeam, 'medical clearance');
        }
    }

    /**
     * Validate current wrestlers for TagTeam suspension.
     *
     * @param  TagTeam  $tagTeam  The TagTeam to validate
     * @throws CannotBeSuspendedException When wrestlers prevent TagTeam suspension
     */
    private function validateCurrentWrestlers(TagTeam $tagTeam): void
    {
        $currentWrestlers = $tagTeam->currentWrestlers()->get();

        if ($currentWrestlers->isEmpty()) {
            throw CannotBeSuspendedException::requiresAuthorization($tagTeam, 'active wrestler verification');
        }

        foreach ($currentWrestlers as $wrestler) {
            // Check if wrestler is already suspended
            if ($wrestler->isSuspended()) {
                $name = $wrestler->getAttribute('name') ?? 'Unknown wrestler';
                throw CannotBeSuspendedException::requiresAuthorization($tagTeam, "individual wrestler suspension review for {$name}");
            }

            // Check if wrestler is injured (might prevent suspension)
            if ($wrestler->isInjured()) {
                $name = $wrestler->getAttribute('name') ?? 'Unknown wrestler';
                throw CannotBeSuspendedException::requiresAuthorization($tagTeam, "medical clearance for {$name}");
            }

            // Ensure the wrestler can be suspended
            if (! $wrestler->canBeSuspended()) {
                $name = $wrestler->getAttribute('name') ?? 'Unknown wrestler';
                throw CannotBeSuspendedException::requiresAuthorization($tagTeam, "individual wrestler suspension eligibility review for {$name}");
            }
        }
    }

    /**
     * Check if the TagTeam is unemployed.
     *
     * @param  TagTeam  $tagTeam  The TagTeam to check
     * @return bool True if unemployed, false otherwise
     */
    private function isUnemployed(TagTeam $tagTeam): bool
    {
        return $tagTeam->hasStatus(EmploymentStatus::Unemployed);
    }

    /**
     * Check if the TagTeam is released.
     *
     * @param  TagTeam  $tagTeam  The TagTeam to check
     * @return bool True if released, false otherwise
     */
    private function isReleased(TagTeam $tagTeam): bool
    {
        return $tagTeam->hasStatus(EmploymentStatus::Released);
    }
}
