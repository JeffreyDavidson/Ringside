<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualSuspensionEligibility
{
    public function canSuspend(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanSuspend($individual);

            return true;
        } catch (CannotBeSuspendedException) {
            return false;
        }
    }

    public function ensureCanSuspend(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->status === EmploymentStatus::Unemployed) {
            throw CannotBeSuspendedException::unemployed($individual);
        }

        if ($individual->isReleased()) {
            throw CannotBeSuspendedException::released($individual);
        }

        if ($individual->currentRetirement()->exists()) {
            throw CannotBeSuspendedException::retired($individual);
        }

        if ($individual->futureEmployment()->exists()) {
            throw CannotBeSuspendedException::hasFutureEmployment($individual);
        }

        if ($individual->isInjured()) {
            throw CannotBeSuspendedException::injured($individual);
        }

        if ($individual->currentSuspension()->exists()) {
            throw CannotBeSuspendedException::suspended($individual);
        }
    }

    public function canReinstate(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanReinstate($individual);

            return true;
        } catch (CannotBeReinstatedException) {
            return false;
        }
    }

    public function ensureCanReinstate(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->isInjured()) {
            throw CannotBeReinstatedException::injured($individual);
        }

        if (! $individual->currentSuspension()->exists()) {
            throw CannotBeReinstatedException::available($individual);
        }

        if (! $individual->currentEmployment()->exists() && ! $individual->futureEmployment()->exists()) {
            throw CannotBeReinstatedException::unemployed($individual);
        }

        if ($individual->futureEmployment()->exists()) {
            throw CannotBeReinstatedException::hasFutureEmployment($individual);
        }

        if ($individual->currentRetirement()->exists()) {
            throw CannotBeReinstatedException::retired($individual);
        }
    }
}
