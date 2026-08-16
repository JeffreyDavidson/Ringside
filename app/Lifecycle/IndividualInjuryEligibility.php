<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualInjuryEligibility
{
    public function canInjure(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanInjure($individual);

            return true;
        } catch (CannotBeInjuredException) {
            return false;
        }
    }

    public function ensureCanInjure(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->hasNoCurrentOrFutureEmployment()) {
            throw CannotBeInjuredException::unemployed($individual);
        }

        if ($individual->isRetired()) {
            throw CannotBeInjuredException::retired($individual);
        }

        if ($individual->hasFutureEmployment()) {
            throw CannotBeInjuredException::hasFutureEmployment($individual);
        }

        if ($individual->isSuspended()) {
            throw CannotBeInjuredException::suspended($individual);
        }

        if ($individual->isInjured()) {
            throw CannotBeInjuredException::injured($individual);
        }
    }

    public function canHeal(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanHeal($individual);

            return true;
        } catch (CannotBeClearedFromInjuryException) {
            return false;
        }
    }

    public function ensureCanHeal(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->isInjured()) {
            throw CannotBeClearedFromInjuryException::notInjured($individual);
        }
    }
}
