<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

/** @mixin Wrestler|Manager|Referee */
trait ValidatesIndividualInjury
{
    public function canBeInjured(): bool
    {
        try {
            $this->ensureCanBeInjured();

            return true;
        } catch (CannotBeInjuredException) {
            return false;
        }
    }

    public function ensureCanBeInjured(): void
    {
        if ($this->isNotInEmployment()) {
            throw CannotBeInjuredException::unemployed($this);
        }

        if ($this->isRetired()) {
            throw CannotBeInjuredException::retired($this);
        }

        if ($this->hasFutureEmployment()) {
            throw CannotBeInjuredException::hasFutureEmployment($this);
        }

        if ($this->isSuspended()) {
            throw CannotBeInjuredException::suspended($this);
        }

        if ($this->isInjured()) {
            throw CannotBeInjuredException::injured($this);
        }
    }

    public function canBeClearedFromInjury(): bool
    {
        try {
            $this->ensureCanBeClearedFromInjury();

            return true;
        } catch (CannotBeClearedFromInjuryException) {
            return false;
        }
    }

    public function ensureCanBeClearedFromInjury(): void
    {
        if (! $this->isInjured()) {
            throw CannotBeClearedFromInjuryException::notInjured($this);
        }
    }

    public function canBeHealed(): bool
    {
        return $this->canBeClearedFromInjury();
    }

    public function ensureCanBeHealed(): void
    {
        $this->ensureCanBeClearedFromInjury();
    }
}
