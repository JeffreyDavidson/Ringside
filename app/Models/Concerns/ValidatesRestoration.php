<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Status\CannotBeRestoredException;
use App\Models\Wrestlers\Wrestler;

trait ValidatesRestoration
{
    /**
     * Check if the entity can be restored to a tag team.
     */
    public function canBeRestored(bool $forceReunite = false): bool
    {
        // Basic availability checks
        if (! $this->exists || $this->trashed()) {
            return false;
        }

        if ($this->isRetired() || ! $this->isEmployed()) {
            return false;
        }

        if ($this->isInjured()) {
            return false;
        }

        // Handle tag team conflicts (only for wrestlers who can be tag team members)
        if ($this instanceof Wrestler) {
            $currentTagTeam = $this->currentTagTeam()->first();
            if ($currentTagTeam !== null && ! $forceReunite) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ensure the entity can be restored to a tag team.
     *
     * @throws CannotBeRestoredException
     */
    public function ensureCanBeRestored(bool $forceReunite = false): void
    {
        if (! $this->canBeRestored($forceReunite)) {
            throw new CannotBeRestoredException();
        }
    }
}
