<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster\Individuals;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Lifecycle\Concerns\AnswersEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class IndividualRetirementEligibility
{
    use AnswersEligibility;

    public function canRetire(Wrestler|Manager|Referee $individual): bool
    {
        return $this->passes(fn () => $this->ensureCanRetire($individual), CannotBeRetiredException::class);
    }

    public function ensureCanRetire(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->status === EmploymentStatus::Unemployed) {
            throw CannotBeRetiredException::unemployed($individual);
        }

        if ($individual->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment($individual);
        }

        if ($individual->hasCurrentRetirement()) {
            throw CannotBeRetiredException::alreadyRetired($individual);
        }
    }

    public function canUnretire(Wrestler|Manager|Referee $individual): bool
    {
        return $this->passes(fn () => $this->ensureCanUnretire($individual), CannotBeUnretiredException::class);
    }

    public function ensureCanUnretire(Wrestler|Manager|Referee $individual): void
    {
        if ($individual->trashed()) {
            throw CannotBeUnretiredException::deleted($individual);
        }

        if (! $individual->hasCurrentRetirement()) {
            throw CannotBeUnretiredException::notRetired($individual);
        }
    }
}
