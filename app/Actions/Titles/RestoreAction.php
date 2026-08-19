<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Lifecycle\DeletionStateManager;
use App\Lifecycle\TitleDeletionEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly TitleDeletionEligibility $eligibility,
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
        DB::transaction(function () use ($title): void {
            $lockedTitle = Title::query()
                ->withTrashed()
                ->whereKey($title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanRestore($lockedTitle);
            $this->deletionState->restore($lockedTitle, now());
        });
    }
}
