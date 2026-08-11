<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Individuals\CannotBeRestoredException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

/** @mixin Wrestler|Manager|Referee */
trait ValidatesIndividualRestoration
{
    public function canBeRestored(): bool
    {
        try {
            $this->ensureCanBeRestored();

            return true;
        } catch (CannotBeRestoredException) {
            return false;
        }
    }

    /** @throws CannotBeRestoredException */
    public function ensureCanBeRestored(): void
    {
        if (! $this->exists || ! $this->trashed()) {
            throw CannotBeRestoredException::notDeleted($this);
        }
    }
}
