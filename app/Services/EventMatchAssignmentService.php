<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Matches\EventMatch;
use Closure;
use Illuminate\Support\Facades\DB;

final class EventMatchAssignmentService
{
    /**
     * Execute match assignment work while holding the match row lock.
     *
     * @param  Closure(EventMatch): void  $assignment
     */
    public function execute(EventMatch $eventMatch, Closure $assignment): void
    {
        DB::transaction(function () use ($eventMatch, $assignment): void {
            $lockedMatch = EventMatch::query()
                ->whereKey($eventMatch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $assignment($lockedMatch);
        });
    }
}
