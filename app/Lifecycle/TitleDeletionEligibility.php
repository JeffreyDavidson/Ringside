<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Exceptions\Titles\CannotBeRestoredException;
use App\Models\Titles\Title;

final class TitleDeletionEligibility
{
    public function canRestore(Title $title): bool
    {
        try {
            $this->ensureCanRestore($title);

            return true;
        } catch (CannotBeRestoredException) {
            return false;
        }
    }

    public function ensureCanRestore(Title $title): void
    {
        if (! $title->trashed()) {
            throw CannotBeRestoredException::notDeleted($title);
        }

        $conflictingTitle = Title::query()
            ->whereName($title->name)
            ->whereKeyNot($title->getKey())
            ->first();

        if ($conflictingTitle !== null) {
            throw CannotBeRestoredException::nameConflict($title, $conflictingTitle->name);
        }
    }
}
