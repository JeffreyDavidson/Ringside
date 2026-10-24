<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualEmploymentEligibility
{
    use AnswersEligibility;

    public function canEmploy(Wrestler|Manager|Referee $individual): bool
    {
        return $this->passes(fn () => $this->ensureCanEmploy($individual), CannotBeEmployedException::class);
    }

    public function ensureCanEmploy(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->hasCurrentEmployment()) {
            throw CannotBeEmployedException::employed($individual);
        }

        if ($individual->hasFutureEmployment()) {
            throw CannotBeEmployedException::hasFutureEmployment($individual);
        }

        if ($individual->hasCurrentRetirement()) {
            throw CannotBeEmployedException::retired($individual);
        }
    }

    public function canRelease(Wrestler|Manager|Referee $individual): bool
    {
        return $this->passes(fn () => $this->ensureCanRelease($individual), CannotBeReleasedException::class);
    }

    public function ensureCanRelease(Wrestler|Manager|Referee $individual): void
    {
        if (! $individual->hasCurrentEmployment() && ! $individual->hasFutureEmployment()) {
            throw CannotBeReleasedException::unemployed($individual);
        }

        if ($individual->hasFutureEmployment()) {
            throw CannotBeReleasedException::hasFutureEmployment($individual);
        }
    }
}
