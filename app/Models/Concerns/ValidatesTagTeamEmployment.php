<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\TagTeams\CannotBeEmployedException;
use App\Models\TagTeams\TagTeam;

/** @mixin TagTeam */
trait ValidatesTagTeamEmployment
{
    public function canBeEmployed(): bool
    {
        if ($this->isEmployed()) {
            return false;
        }

        if ($this->isRetired()) {
            return false;
        }

        return $this->currentWrestlers->isNotEmpty();
    }

    /** @throws CannotBeEmployedException */
    public function ensureCanBeEmployed(): void
    {
        if ($this->isEmployed()) {
            throw CannotBeEmployedException::alreadyEmployed($this);
        }

        if ($this->isRetired()) {
            throw CannotBeEmployedException::retired($this);
        }

        if ($this->currentWrestlers->isEmpty()) {
            throw CannotBeEmployedException::partnersUnavailable($this, 'No current partners available');
        }
    }
}
