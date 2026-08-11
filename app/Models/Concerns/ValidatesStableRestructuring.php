<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Stables\CannotBeMergedException;
use App\Exceptions\Roster\Stables\CannotBeSplitException;
use App\Models\Stables\Stable;

/** @mixin Stable */
trait ValidatesStableRestructuring
{
    public function canBeSplit(): bool
    {
        try {
            $this->ensureCanBeSplit();

            return true;
        } catch (CannotBeSplitException) {
            return false;
        }
    }

    /** @throws CannotBeSplitException */
    public function ensureCanBeSplit(): void
    {
        if ($this->isRetired()) {
            throw CannotBeSplitException::retired($this);
        }

        if (! $this->isCurrentlyActive()) {
            throw CannotBeSplitException::notActive($this);
        }

        $minimumMemberCount = static::MIN_MEMBERS_COUNT * 2;
        $currentMemberCount = $this->getCurrentMembersData()->getTotalMemberCount();

        if ($currentMemberCount < $minimumMemberCount) {
            throw CannotBeSplitException::insufficientMembers($this, $currentMemberCount, $minimumMemberCount);
        }
    }

    public function canBeMergedWith(Stable $otherStable): bool
    {
        try {
            $this->ensureCanBeMergedWith($otherStable);

            return true;
        } catch (CannotBeMergedException) {
            return false;
        }
    }

    /** @throws CannotBeMergedException */
    public function ensureCanBeMergedWith(Stable $otherStable): void
    {
        if ($this->is($otherStable)) {
            throw CannotBeMergedException::selfMerge($this);
        }

        if ($this->isRetired()) {
            throw CannotBeMergedException::primaryRetired($this);
        }

        if ($otherStable->isRetired()) {
            throw CannotBeMergedException::secondaryRetired($otherStable);
        }

        if (! $this->isCurrentlyActive()) {
            throw CannotBeMergedException::primaryNotActive($this);
        }

        if (! $otherStable->isCurrentlyActive()) {
            throw CannotBeMergedException::secondaryNotActive($otherStable);
        }
    }
}
