<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\TagTeams\CannotBeReleasedException;
use App\Models\TagTeams\TagTeam;

/** @mixin TagTeam */
trait ValidatesTagTeamRelease
{
    public function canBeReleased(): bool
    {
        return $this->isEmployed();
    }

    /** @throws CannotBeReleasedException */
    public function ensureCanBeReleased(): void
    {
        if (! $this->isEmployed()) {
            throw CannotBeReleasedException::notEmployed($this);
        }
    }
}
