<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualEmploymentEligibility
{
    public function canEmploy(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanEmploy($individual);

            return true;
        } catch (CannotBeEmployedException) {
            return false;
        }
    }

    public function ensureCanEmploy(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->isEmployed()) {
            throw CannotBeEmployedException::employed($individual);
        }

        if ($individual->futureEmployment()->exists()) {
            throw CannotBeEmployedException::hasFutureEmployment($individual);
        }

        if ($individual->isRetired()) {
            throw CannotBeEmployedException::retired($individual);
        }
    }

    public function canRelease(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanRelease($individual);

            return true;
        } catch (CannotBeReleasedException) {
            return false;
        }
    }

    public function ensureCanRelease(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->currentEmployment()->exists() && ! $individual->futureEmployment()->exists()) {
            throw CannotBeReleasedException::unemployed($individual);
        }

        if ($individual->futureEmployment()->exists()) {
            throw CannotBeReleasedException::hasFutureEmployment($individual);
        }

        if ($individual->isRetired()) {
            throw CannotBeReleasedException::retired($individual);
        }
    }
}
