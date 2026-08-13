<?php

declare(strict_types=1);

namespace App\Actions\Matches;

use App\Lifecycle\DeletionStateManager;
use App\Models\Matches\EventMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(private readonly DeletionStateManager $deletionState) {}

    public function handle(EventMatch $eventMatch, ?Carbon $deletedAt = null): void
    {
        DB::transaction(function () use ($eventMatch, $deletedAt): void {
            $lockedMatch = EventMatch::query()->lockForUpdate()->findOrFail($eventMatch->getKey());

            $this->deletionState->delete($lockedMatch, $deletedAt ?? now());
        });
    }
}
