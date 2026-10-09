<?php

declare(strict_types=1);

namespace App\Lifecycle\Titles;

use App\Enums\Titles\TitleLifecycleTransition;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Titles\CannotBeDebutedException;
use App\Exceptions\Titles\CannotBePulledException;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Exceptions\Titles\CannotBeRetiredException;
use App\Exceptions\Titles\CannotBeUnretiredException;
use App\Models\Titles\Title;

final class TitleLifecycleEligibility
{
    public function allows(Title $title, TitleLifecycleTransition $transition): bool
    {
        try {
            $this->ensureAllowed($title, $transition);

            return true;
        } catch (BaseBusinessException) {
            return false;
        }
    }

    public function ensureAllowed(Title $title, TitleLifecycleTransition $transition): void
    {
        match ($transition) {
            TitleLifecycleTransition::Debut => $this->ensureCanDebut($title),
            TitleLifecycleTransition::Pull => $this->ensureCanPull($title),
            TitleLifecycleTransition::Reinstate => $this->ensureCanReinstate($title),
            TitleLifecycleTransition::Retire => $this->ensureCanRetire($title),
            TitleLifecycleTransition::Unretire => $this->ensureCanUnretire($title),
        };
    }

    private function ensureCanDebut(Title $title): void
    {
        if ($title->hasActivityHistory()) {
            throw CannotBeDebutedException::alreadyDebuted($title);
        }
    }

    private function ensureCanReinstate(Title $title): void
    {
        if (! $title->hasActivityHistory()) {
            throw CannotBeReinstatedException::neverActivated($title);
        }

        if ($title->hasCurrentActivityPeriod()) {
            throw CannotBeReinstatedException::active($title);
        }

        // A debut that has not started yet is moved by changing the debut date, not by reinstating.
        if ($title->hasFutureActivityPeriod() && ! $title->previousActivityPeriods()->exists()) {
            throw CannotBeReinstatedException::scheduledDebut($title);
        }

        if ($title->hasCurrentRetirement()) {
            throw CannotBeReinstatedException::retired($title);
        }
    }

    private function ensureCanPull(Title $title): void
    {
        if (! $title->hasCurrentActivityPeriod()) {
            throw CannotBePulledException::notActive($title);
        }
    }

    private function ensureCanRetire(Title $title): void
    {
        if ($title->hasCurrentRetirement()) {
            throw CannotBeRetiredException::alreadyRetired($title);
        }

        if (! $title->hasActivityHistory()) {
            throw CannotBeRetiredException::unactivated($title);
        }

        if ($title->hasFutureActivityPeriod()) {
            throw CannotBeRetiredException::hasFutureDebut($title);
        }
    }

    private function ensureCanUnretire(Title $title): void
    {
        if ($title->trashed()) {
            throw CannotBeUnretiredException::deleted($title);
        }

        if (! $title->hasCurrentRetirement()) {
            throw CannotBeUnretiredException::notRetired($title);
        }
    }
}
