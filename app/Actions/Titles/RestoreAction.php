<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use App\Services\TitleDeletionService;

class RestoreAction
{
    public function __construct(
        private readonly TitleDeletionService $deletion,
    ) {}

    /**
     * Restore a soft-deleted title.
     *
     * This handles the complete title restoration workflow:
     * - Restores the soft-deleted title record
     * - Makes the title available for future championship competition
     * - Preserves all championship history, status changes, and match records
     * - Does not automatically restore active status
     * - Requires separate debut/reinstate action to make title active again
     *
     * @param  Title  $title  The soft-deleted title to restore
     */
    public function handle(Title $title): void
    {
        $this->deletion->restore($title, now());
    }
}
