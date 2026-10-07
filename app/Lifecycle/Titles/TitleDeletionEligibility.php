<?php

declare(strict_types=1);

namespace App\Lifecycle\Titles;

use App\Exceptions\Titles\CannotBeRestoredException;
use App\Models\Titles\Title;

final class TitleDeletionEligibility
{
    public function ensureCanRestore(Title $title): void
    {
        if (! $title->trashed()) {
            throw CannotBeRestoredException::notDeleted($title);
        }

        $conflictingTitle = Title::query()
            ->whereNameConflictsWith($title)
            ->first();

        if ($conflictingTitle !== null) {
            throw CannotBeRestoredException::nameConflict($title, $conflictingTitle->name);
        }
    }
}
