<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Models\Matches\EventMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(private readonly DeletionStateManager $deletionState) {}

    public function handle(EventMatch $eventMatch, ?Carbon $deletedAt = null): void
    {
        DB::transaction(function () use ($eventMatch, $deletedAt): void {
            $lockedMatch = $eventMatch->refreshForUpdate();

            $this->deletionState->delete($lockedMatch, $deletedAt ?? now());
        });
    }
}
