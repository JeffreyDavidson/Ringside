<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Stables\CannotBeDisbandedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Exceptions\Roster\Stables\CannotBeReunitedException;

/**
 * Provides stable-specific validation for establishment, disbandment, and reunion.
 */
trait ValidatesStableActivity
{
    public function canBeEstablished(): bool
    {
        return ! $this->hasActivityPeriods() && ! $this->isRetired();
    }

    /**
     * @throws CannotBeEstablishedException
     */
    public function ensureCanBeEstablished(): void
    {
        if ($this->hasActivityPeriods()) {
            throw CannotBeEstablishedException::established($this);
        }

        if ($this->isRetired()) {
            throw CannotBeEstablishedException::retired($this);
        }
    }

    public function canBeDisbanded(): bool
    {
        return $this->hasActivityPeriods()
            && $this->isCurrentlyActive()
            && ! $this->hasFutureActivation()
            && ! $this->isRetired();
    }

    /**
     * @throws CannotBeDisbandedException
     */
    public function ensureCanBeDisbanded(): void
    {
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
        return $this->hasActivityPeriods()
            && ! $this->isCurrentlyActive()
            && ! $this->hasFutureActivation()
            && ! $this->isRetired()
            && $this->getAvailableFormerMembers()->count() >= static::MIN_MEMBERS_COUNT
            && $this->getUnavailableKeyFormerMembers()->isEmpty();
    }

    /**
     * @throws CannotBeReunitedException
     */
    public function ensureCanBeReunited(): void
    {
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
