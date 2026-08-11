<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Roster\Individuals\CannotBeDeletedException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

/** @mixin Wrestler|Manager|Referee */
trait ValidatesIndividualDeletion
{
    public function canBeDeleted(): bool
    {
        try {
            $this->ensureCanBeDeleted();

            return true;
        } catch (CannotBeDeletedException) {
            return false;
        }
    }

    /** @throws CannotBeDeletedException */
    public function ensureCanBeDeleted(): void
    {
        if (! $this->exists || $this->trashed()) {
            throw CannotBeDeletedException::alreadyDeleted($this);
        }
    }
}
