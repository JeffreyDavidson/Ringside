<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualInjuryEligibility
{
    use AnswersEligibility;

    public function canInjure(Wrestler|Manager|Referee $individual): bool
    {
        return $this->passes(fn () => $this->ensureCanInjure($individual), CannotBeInjuredException::class);
    }

    public function ensureCanInjure(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->hasCurrentEmployment() && ! $individual->hasFutureEmployment()) {
            throw CannotBeInjuredException::unemployed($individual);
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

    public function canBeClearedFromInjury(Wrestler|Manager|Referee $individual): bool
    {
        return $this->passes(fn () => $this->ensureCanBeClearedFromInjury($individual), CannotBeClearedFromInjuryException::class);
    }

    public function ensureCanBeClearedFromInjury(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->isInjured()) {
            throw CannotBeClearedFromInjuryException::notInjured($individual);
        }
    }
}
