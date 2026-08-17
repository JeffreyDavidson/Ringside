<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Enums\Titles\TitleLifecycleTransition;
use App\Exceptions\BaseBusinessException;
use App\Exceptions\Titles\CannotBeDebutedException;
use App\Exceptions\Titles\CannotBePulledException;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Exceptions\Titles\CannotBeRetiredException;
use App\Exceptions\Titles\CannotBeUnretiredException;
use App\Models\Titles\Title;

class TitleLifecycleEligibility
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
        if ($title->hasActivityPeriods()) {
            throw CannotBeDebutedException::alreadyDebuted($title);
        }

        if ($title->isRetired()) {
            throw CannotBeDebutedException::retired($title);
        }
    }

    private function ensureCanReinstate(Title $title): void
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

    private function ensureCanPull(Title $title): void
    {
        if (! $title->isCurrentlyActive()) {
            throw CannotBePulledException::notActive($title);
        }

        if ($title->isRetired()) {
            throw CannotBePulledException::retired($title);
        }
    }

    private function ensureCanRetire(Title $title): void
    {
        if ($title->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($title);
        }

        if (! $title->hasActivityPeriods()) {
            throw CannotBeRetiredException::unactivated($title);
        }

        if ($title->futureActivityPeriod()->exists()) {
            throw CannotBeRetiredException::hasFutureDebut($title);
        }
    }

    private function ensureCanUnretire(Title $title): void
    {
        if ($title->trashed()) {
            throw CannotBeUnretiredException::deleted($title);
        }

        if (! $title->isRetired()) {
            throw CannotBeUnretiredException::notRetired($title);
        }
    }
}
