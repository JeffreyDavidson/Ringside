<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\Data\CannotBeRestoredException;

trait ValidatesRestoration
{
    /**
     * Check if a soft-deleted entity can be restored.
     *
     * The entity must exist in the database and currently be soft-deleted
     * (otherwise there's nothing to restore). All relationship state
     * (employment, tag teams, stables) is restored separately by the
     * action's caller via dedicated actions, not part of this validation.
     */
    public function canBeRestored(bool $forceReunite = false): bool
    {
        if (! $this->exists) {
            return false;
        }

        if (! $this->trashed()) {
            return false;
        }

        return true;
    }

    /**
     * Ensure the entity can be restored from soft delete.
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
