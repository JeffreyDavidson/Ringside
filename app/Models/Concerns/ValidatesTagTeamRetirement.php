<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\TagTeams\CannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeUnretiredException;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

/** @mixin TagTeam */
trait ValidatesTagTeamRetirement
{
    public function canBeRetired(): bool
    {
        if ($this->isRetired()) {
            return false;
        }

        return $this->isEmployed();
    }

    /** @throws CannotBeRetiredException */
    public function ensureCanBeRetired(): void
    {
        if ($this->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($this);
        }

        if (! $this->isEmployed()) {
            throw CannotBeRetiredException::notEmployed($this);
        }
    }

    public function canBeUnretired(bool $requireAvailablePartners = true): bool
    {
        try {
            $this->ensureCanBeUnretired($requireAvailablePartners);

            return true;
        } catch (CannotBeUnretiredException) {
            return false;
        }
    }

    /** @throws CannotBeUnretiredException */
    public function ensureCanBeUnretired(bool $requireAvailablePartners = true): void
    {
        if (! $this->isRetired()) {
            throw CannotBeUnretiredException::notRetired($this);
        }

        $conflictingTeam = static::query()
            ->where('name', $this->name)
            ->whereKeyNot($this->getKey())
            ->whereHas('employments', fn ($query) => $query->whereNull('ended_at'))
            ->first();

        if ($conflictingTeam) {
            throw CannotBeUnretiredException::nameConflict($this, $conflictingTeam->name);
        }

        if (! $requireAvailablePartners) {
            return;
        }

        $currentPartners = $this->currentWrestlers;

        if ($currentPartners->isEmpty()) {
            throw CannotBeUnretiredException::noAvailablePartners($this);
        }

        $minimumPartners = 2;

        if ($currentPartners->count() < $minimumPartners) {
            throw CannotBeUnretiredException::insufficientPartners(
                $this,
                $currentPartners->count(),
                $minimumPartners,
            );
        }

        $unavailablePartners = $currentPartners->filter(
            fn (Wrestler $wrestler): bool => $wrestler->isInjured(),
        );

        if ($unavailablePartners->isNotEmpty()) {
            throw CannotBeUnretiredException::keyPartnersUnavailable(
                $this,
                $unavailablePartners->pluck('name')->join(', '),
            );
        }
    }
}
