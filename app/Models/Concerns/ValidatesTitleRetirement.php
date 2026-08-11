<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Titles\CannotBeRetiredException;
use App\Exceptions\Titles\CannotBeUnretiredException;
use App\Models\Titles\Title;

/**
 * @mixin Title
 */
trait ValidatesTitleRetirement
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
        if ($this->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($this);
        }

        if (! $this->hasActivityPeriods()) {
            throw CannotBeRetiredException::unactivated($this);
        }

        if ($this->hasFutureDebut()) {
            throw CannotBeRetiredException::hasFutureDebut($this);
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
