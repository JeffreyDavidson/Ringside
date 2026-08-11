<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Stables\CannotBeDeletedException;
use App\Exceptions\Roster\Stables\CannotBeRestoredException;
use App\Models\Stables\Stable;

/** @mixin Stable */
trait ValidatesStableDeletion
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

        if ($this->isCurrentlyActive()) {
            throw CannotBeDeletedException::currentlyActive($this);
        }

        if ($this->hasFutureEstablishment()) {
            throw CannotBeDeletedException::futureEstablishmentScheduled($this);
        }

        if ($this->hasCurrentMembers()) {
            throw CannotBeDeletedException::hasCurrentMembers(
                $this,
                $this->getCurrentMembersData()->getTotalMemberCount(),
            );
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

        $conflictingStable = static::query()
            ->where('name', $this->name)
            ->whereKeyNot($this->getKey())
            ->first();

        if ($conflictingStable) {
            throw CannotBeRestoredException::nameConflict($this, $conflictingStable->name);
        }
    }
}
