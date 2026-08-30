<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualRetirementEligibility
{
    public function canRetire(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanRetire($individual);

            return true;
        } catch (CannotBeRetiredException) {
            return false;
        }
    }

    public function ensureCanRetire(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->status === EmploymentStatus::Unemployed) {
            throw CannotBeRetiredException::unemployed($individual);
        }

        if ($individual->futureEmployment()->exists()) {
            throw CannotBeRetiredException::hasFutureEmployment($individual);
        }

        if ($individual->currentRetirement()->exists()) {
            throw CannotBeRetiredException::alreadyRetired($individual);
        }
    }

    public function canUnretire(Wrestler|Manager|Referee $individual): bool
    {
        try {
            $this->ensureCanUnretire($individual);

            return true;
        } catch (CannotBeUnretiredException) {
            return false;
        }
    }

    public function ensureCanUnretire(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->trashed()) {
            throw CannotBeUnretiredException::deleted($individual);
        }

        if (! $individual->currentRetirement()->exists()) {
            throw CannotBeUnretiredException::notRetired($individual);
        }
    }
}
