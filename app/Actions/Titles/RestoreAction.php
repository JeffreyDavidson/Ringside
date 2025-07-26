<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * $deletedTitle = Title::onlyTrashed()->find(1);
     * RestoreAction::run($deletedTitle);
     * ```
     */
    public function handle(Title $title): void
    {
        DB::transaction(function () use ($title): void {
            $title->restore();
        });
    }
}
