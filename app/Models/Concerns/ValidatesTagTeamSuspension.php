<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\TagTeams\CannotBeReinstatedException;
use App\Exceptions\Roster\TagTeams\CannotBeSuspendedException;
use App\Models\TagTeams\TagTeam;

/** @mixin TagTeam */
trait ValidatesTagTeamSuspension
{
    public function canBeSuspended(): bool
    {
        if (! $this->isEmployed()) {
            return false;
        }

        return ! $this->isSuspended();
    }

    /** @throws CannotBeSuspendedException */
    public function ensureCanBeSuspended(): void
    {
        if (! $this->isEmployed()) {
            throw CannotBeSuspendedException::notEmployed($this);
        }

        if ($this->isSuspended()) {
            throw CannotBeSuspendedException::alreadySuspended($this);
        }
    }

    public function canBeReinstated(): bool
    {
        if (! $this->isSuspended()) {
            return false;
        }

        return $this->isEmployed();
    }

    /** @throws CannotBeReinstatedException */
    public function ensureCanBeReinstated(): void
    {
        if (! $this->isSuspended()) {
            throw CannotBeReinstatedException::notSuspended($this);
        }

        if (! $this->isEmployed()) {
            throw CannotBeReinstatedException::notEmployed($this);
        }
    }
}
