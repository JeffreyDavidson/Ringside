<?php

declare(strict_types=1);

namespace App\Services\Matches;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Models\Matches\EventMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class EventMatchDeletionService
{
    public function __construct(private readonly DeletionStateManager $deletionState) {}

    public function delete(EventMatch $eventMatch, Carbon $deletionDate): void
    {
        DB::transaction(function () use ($eventMatch, $deletionDate): void {
            $lockedMatch = $eventMatch->refreshForUpdate();

            $this->deletionState->delete($lockedMatch, $deletionDate);
        });
    }
}
