<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Stables\CannotBeRetiredException;
use App\Exceptions\Roster\Stables\CannotBeUnretiredException;
use App\Models\Stables\Stable;

/** @mixin Stable */
trait ValidatesStableRetirement
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

    /** @throws CannotBeRetiredException */
    public function ensureCanBeRetired(): void
    {
        if ($this->trashed()) {
            throw CannotBeRetiredException::deleted($this);
        }

        if ($this->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($this);
        }

        if (! $this->hasActivityPeriods() || (! $this->isCurrentlyActive() && $this->hasFutureActivation())) {
            throw CannotBeRetiredException::notActive($this);
        }
    }

    public function canBeUnretired(bool $requireFormerMembers = true): bool
    {
        try {
            $this->ensureCanBeUnretired($requireFormerMembers);

            return true;
        } catch (CannotBeUnretiredException) {
            return false;
        }
    }

    /** @throws CannotBeUnretiredException */
    public function ensureCanBeUnretired(bool $requireFormerMembers = true): void
    {
        if ($this->trashed()) {
            throw CannotBeUnretiredException::deleted($this);
        }

        if (! $this->isRetired()) {
            throw CannotBeUnretiredException::notRetired($this);
        }

        $conflictingStable = static::query()
            ->where('name', $this->name)
            ->whereKeyNot($this->getKey())
            ->whereHas('activityPeriods', fn ($query) => $query->whereNull('ended_at'))
            ->first();

        if ($conflictingStable) {
            throw CannotBeUnretiredException::nameConflict($this, $conflictingStable->name);
        }

        if (! $requireFormerMembers) {
            return;
        }

        $availableFormerMembers = $this->getAvailableFormerMembers();

        if ($availableFormerMembers->isEmpty()) {
            throw CannotBeUnretiredException::noAvailableFormerMembers($this);
        }

        if ($availableFormerMembers->count() < static::MIN_MEMBERS_COUNT) {
            throw CannotBeUnretiredException::insufficientFormerMembers(
                $this,
                $availableFormerMembers->count(),
                static::MIN_MEMBERS_COUNT,
            );
        }

        $unavailableKeyMembers = $this->getUnavailableKeyFormerMembers();

        if ($unavailableKeyMembers->isNotEmpty()) {
            throw CannotBeUnretiredException::keyMembersUnavailable(
                $this,
                $unavailableKeyMembers->pluck('name')->join(', '),
            );
        }
    }
}
