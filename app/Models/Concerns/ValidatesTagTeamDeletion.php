<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\TagTeams\CannotBeDeletedException;
use App\Exceptions\Roster\TagTeams\CannotBeRestoredException;
use App\Models\TagTeams\TagTeam;

/** @mixin TagTeam */
trait ValidatesTagTeamDeletion
{
    public function canBeDeleted(): bool
    {
        try {
            $this->ensureCanBeDeleted();

            return true;
        } catch (CannotBeDeletedException) {
            return false;
        }
    }

    /** @throws CannotBeDeletedException */
    public function ensureCanBeDeleted(): void
    {
        if ($this->trashed()) {
            throw CannotBeDeletedException::alreadyDeleted($this);
        }

        if ($this->isRetired()) {
            throw CannotBeDeletedException::stillRetired($this);
        }

        if ($this->isEmployed()) {
            throw CannotBeDeletedException::stillEmployed($this);
        }

        if ($this->isSuspended()) {
            throw CannotBeDeletedException::stillSuspended($this);
        }
    }

    public function canBeRestored(): bool
    {
        try {
            $this->ensureCanBeRestored();

            return true;
        } catch (CannotBeRestoredException) {
            return false;
        }
    }

    /** @throws CannotBeRestoredException */
    public function ensureCanBeRestored(): void
    {
        if (! $this->trashed()) {
            throw CannotBeRestoredException::notDeleted($this);
        }

        $conflictingTeam = static::query()
            ->where('name', $this->name)
            ->whereKeyNot($this->getKey())
            ->whereHas('employments', fn ($query) => $query->whereNull('ended_at'))
            ->first();

        if ($conflictingTeam) {
            throw CannotBeRestoredException::nameConflict($this, $conflictingTeam->name);
        }
    }
}
