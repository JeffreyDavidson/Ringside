<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

/**
 * @mixin Wrestler|Manager|Referee
 */
trait ValidatesIndividualRetirement
{
    public function canBeRetired(): bool
    {
        try {
            $this->ensureCanBeRetired();

            return true;
        } catch (CannotBeRetiredException) {
            return false;
        }
    }

    public function ensureCanBeRetired(): void
    {
        if ($this->hasStatus(EmploymentStatus::Unemployed)) {
            throw CannotBeRetiredException::unemployed($this);
        }

        if ($this->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment($this);
        }

        if ($this->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($this);
        }
    }

    public function canBeUnretired(): bool
    {
        try {
            $this->ensureCanBeUnretired();

            return true;
        } catch (CannotBeUnretiredException) {
            return false;
        }
    }

    public function ensureCanBeUnretired(): void
    {
        if ($this->trashed()) {
            throw CannotBeUnretiredException::deleted($this);
        }

        if (! $this->isRetired()) {
            throw CannotBeUnretiredException::notRetired($this);
        }
    }
}
