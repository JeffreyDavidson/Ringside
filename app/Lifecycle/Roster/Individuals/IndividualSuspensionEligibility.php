<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualSuspensionEligibility
{
    use AnswersEligibility;

    public function canSuspend(Wrestler|Manager|Referee $individual): bool
    {
        return $this->passes(fn () => $this->ensureCanSuspend($individual), CannotBeSuspendedException::class);
    }

    public function ensureCanSuspend(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->status === EmploymentStatus::Unemployed) {
            throw CannotBeSuspendedException::unemployed($individual);
        }

        if ($individual->status === EmploymentStatus::Released) {
            throw CannotBeSuspendedException::released($individual);
        }

        if ($individual->hasCurrentRetirement()) {
            throw CannotBeSuspendedException::retired($individual);
        }

        if ($individual->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment($individual);
        }

        if ($individual->isInjured()) {
            throw CannotBeSuspendedException::injured($individual);
        }

        if ($individual->isSuspended()) {
            throw CannotBeSuspendedException::suspended($individual);
        }
    }

    public function canReinstate(Wrestler|Manager|Referee $individual): bool
    {
        return $this->passes(fn () => $this->ensureCanReinstate($individual), CannotBeReinstatedException::class);
    }

    public function ensureCanReinstate(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->isInjured()) {
            throw CannotBeReinstatedException::injured($individual);
        }

        if (! $individual->isSuspended()) {
            throw CannotBeReinstatedException::available($individual);
        }
    }
}
