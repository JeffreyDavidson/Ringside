<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Models\Stables\Stable;

/** @mixin Stable */
trait ValidatesStableActivity
{
    public function canBeEstablished(): bool
    {
        try {
            $this->ensureCanBeEstablished();

            return true;
        } catch (CannotBeEstablishedException) {
            return false;
        }
    }

    /**
     * @throws CannotBeEstablishedException
     */
    public function ensureCanBeEstablished(): void
    {
        if ($this->trashed()) {
            throw CannotBeEstablishedException::deleted($this);
        }

        if ($this->hasActivityPeriods()) {
            throw CannotBeEstablishedException::established($this);
        }

        if ($this->isRetired()) {
            throw CannotBeEstablishedException::retired($this);
        }
    }

    public function canBeDisbanded(): bool
    {
        try {
            $this->ensureCanBeDisbanded();

            return true;
        } catch (CannotBeDisbandedException) {
            return false;
        }
    }

    /**
     * @throws CannotBeDisbandedException
     */
    public function ensureCanBeDisbanded(): void
    {
        if ($this->trashed()) {
            throw CannotBeDisbandedException::deleted($this);
        }

        if (! $this->hasActivityPeriods()) {
            throw CannotBeDisbandedException::unactivated($this);
        }

        if ($this->hasFutureActivation()) {
            throw CannotBeDisbandedException::hasFutureActivation($this);
        }

        if (! $this->isCurrentlyActive()) {
            throw CannotBeDisbandedException::disbanded($this);
        }

        if ($this->isRetired()) {
            throw CannotBeDisbandedException::retired($this);
        }
    }

    public function canBeReunited(): bool
    {
        try {
            $this->ensureCanBeReunited();

            return true;
        } catch (CannotBeReunitedException) {
            return false;
        }
    }

    /**
     * @throws CannotBeReunitedException
     */
    public function ensureCanBeReunited(): void
    {
        if ($this->trashed()) {
            throw CannotBeReunitedException::deleted($this);
        }

        if (! $this->hasActivityPeriods()) {
            throw CannotBeReunitedException::neverActive($this);
        }

        if ($this->isCurrentlyActive() || $this->hasFutureActivation()) {
            throw CannotBeReunitedException::currentlyActive($this);
        }

        if ($this->isRetired()) {
            throw CannotBeReunitedException::retired($this);
        }

        $availableFormerMembers = $this->getAvailableFormerMembers();
        if ($availableFormerMembers->count() < static::MIN_MEMBERS_COUNT) {
            throw CannotBeReunitedException::insufficientFormerMembers(
                $this,
                $availableFormerMembers->count(),
                static::MIN_MEMBERS_COUNT,
            );
        }

        $unavailableKeyMembers = $this->getUnavailableKeyFormerMembers();
        if ($unavailableKeyMembers->isNotEmpty()) {
            throw CannotBeReunitedException::keyMembersUnavailable(
                $this,
                $unavailableKeyMembers->pluck('name')->join(', '),
            );
        }
    }
}
