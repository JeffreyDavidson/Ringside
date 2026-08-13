<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Exceptions\Titles\CannotBeDebutedException;
use App\Exceptions\Titles\CannotBePulledException;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Exceptions\Titles\CannotBeRetiredException;
use App\Exceptions\Titles\CannotBeUnretiredException;
use App\Models\Titles\Title;

class TitleLifecycleEligibility
{
    public function canDebut(Title $title): bool
    {
        try {
            $this->ensureCanDebut($title);

            return true;
        } catch (CannotBeDebutedException) {
            return false;
        }
    }

    public function ensureCanDebut(Title $title): void
    {
        if ($title->hasActivityPeriods()) {
            throw CannotBeDebutedException::alreadyDebuted($title);
        }

        if ($title->isRetired()) {
            throw CannotBeDebutedException::retired($title);
        }
    }

    public function canReinstate(Title $title): bool
    {
        try {
            $this->ensureCanReinstate($title);

            return true;
        } catch (CannotBeReinstatedException) {
            return false;
        }
    }

    public function ensureCanReinstate(Title $title): void
    {
        if (! $title->hasActivityPeriods()) {
            throw CannotBeReinstatedException::neverActivated($title);
        }

        if ($title->isCurrentlyActive()) {
            throw CannotBeReinstatedException::active($title);
        }

        if ($title->isRetired()) {
            throw CannotBeReinstatedException::retired($title);
        }
    }

    public function canPull(Title $title): bool
    {
        try {
            $this->ensureCanPull($title);

            return true;
        } catch (CannotBePulledException) {
            return false;
        }
    }

    public function ensureCanPull(Title $title): void
    {
        if (! $title->isCurrentlyActive()) {
            throw CannotBePulledException::notActive($title);
        }

        if ($title->isRetired()) {
            throw CannotBePulledException::retired($title);
        }
    }

    public function canRetire(Title $title): bool
    {
        try {
            $this->ensureCanRetire($title);

            return true;
        } catch (CannotBeRetiredException) {
            return false;
        }
    }

    public function ensureCanRetire(Title $title): void
    {
        if ($title->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($title);
        }

        if (! $title->hasActivityPeriods()) {
            throw CannotBeRetiredException::unactivated($title);
        }

        if ($title->hasFutureDebut()) {
            throw CannotBeRetiredException::hasFutureDebut($title);
        }
    }

    public function canUnretire(Title $title): bool
    {
        try {
            $this->ensureCanUnretire($title);

            return true;
        } catch (CannotBeUnretiredException) {
            return false;
        }
    }

    public function ensureCanUnretire(Title $title): void
    {
        if ($title->trashed()) {
            throw CannotBeUnretiredException::deleted($title);
        }

        if (! $title->isRetired()) {
            throw CannotBeUnretiredException::notRetired($title);
        }
    }
}
