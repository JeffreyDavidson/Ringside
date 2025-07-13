<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\Contracts\SuspensionValidationStrategy;
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
     *
     * @throws CannotBeSuspendedException When suspension is not allowed
     */
    public function validate(Model $tagTeam): void
    {
        // Standard TagTeam validation
        $this->validateTagTeamStatus($tagTeam);

        // TagTeam-specific: validate current wrestlers
        $this->validateCurrentWrestlers($tagTeam);
    }

    /**
     * Validate the TagTeam's own status for suspension.
     *
     * @param  Model  $tagTeam  The TagTeam to validate
     *
     * @throws CannotBeSuspendedException When TagTeam status prevents suspension
     */
    private function validateTagTeamStatus(Model $tagTeam): void
    {
        if ($this->isUnemployed($tagTeam)) {
            throw CannotBeSuspendedException::unemployed();
        }

        if ($this->isReleased($tagTeam)) {
            throw CannotBeSuspendedException::released();
        }

        if (method_exists($tagTeam, 'isRetired') && $tagTeam->isRetired()) {
            throw CannotBeSuspendedException::retired();
        }

        if (method_exists($tagTeam, 'hasFutureEmployment') && $tagTeam->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment();
        }

        if (method_exists($tagTeam, 'isSuspended') && $tagTeam->isSuspended()) {
            throw CannotBeSuspendedException::suspended();
        }

        if (method_exists($tagTeam, 'isInjured') && $tagTeam->isInjured()) {
            throw CannotBeSuspendedException::injured();
        }
    }

    /**
     * Validate current wrestlers for TagTeam suspension.
     *
     * @param  Model  $tagTeam  The TagTeam to validate
     *
     * @throws CannotBeSuspendedException When wrestlers prevent TagTeam suspension
     */
    private function validateCurrentWrestlers(Model $tagTeam): void
    {
        $currentWrestlers = method_exists($tagTeam, 'currentWrestlers') ? $tagTeam->currentWrestlers()->get() : collect();

        if ($currentWrestlers->isEmpty()) {
            throw CannotBeSuspendedException::noActiveWrestlers();
        }

        foreach ($currentWrestlers as $wrestler) {
            // Check if wrestler is already suspended
            if (method_exists($wrestler, 'isSuspended') && $wrestler->isSuspended()) {
                $name = method_exists($wrestler, 'getAttribute') ? $wrestler->getAttribute('name') ?? 'Unknown wrestler' : 'Unknown wrestler';
                throw CannotBeSuspendedException::wrestlerAlreadySuspended($name);
            }

            // Check if wrestler is injured (might prevent suspension)
            if (method_exists($wrestler, 'isInjured') && $wrestler->isInjured()) {
                $name = method_exists($wrestler, 'getAttribute') ? $wrestler->getAttribute('name') ?? 'Unknown wrestler' : 'Unknown wrestler';
                throw CannotBeSuspendedException::wrestlerInjured($name);
            }

            // Ensure the wrestler can be suspended
            if (method_exists($wrestler, 'canBeSuspended') && ! $wrestler->canBeSuspended()) {
                $name = method_exists($wrestler, 'getAttribute') ? $wrestler->getAttribute('name') ?? 'Unknown wrestler' : 'Unknown wrestler';
                throw CannotBeSuspendedException::wrestlerCannotBeSuspended($name);
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

    /**
     * Check if the TagTeam is released.
     *
     * @param  Model  $tagTeam  The TagTeam to check
     * @return bool True if released, false otherwise
     */
    private function isReleased(Model $tagTeam): bool
    {
        return method_exists($tagTeam, 'hasStatus') ? $tagTeam->hasStatus(EmploymentStatus::Released) : false;
    }
}
