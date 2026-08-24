<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use Illuminate\Support\Collection;

final class MatchTitleConflictChecker
{
    /**
     * @param  Collection<int, int>  $conflictingEventIds
     * @param  Collection<int, Title>  $titles
     */
    public function ensureCanBeAssigned(Collection $conflictingEventIds, Collection $titles): void
    {
        $conflictingTitleId = EventMatch::query()
            ->forEventIds($conflictingEventIds)
            ->withAnyTitleIds($titles->map(fn (Title $title): int => $title->id))
            ->with('titles:id,name')
            ->get()
            ->flatMap->titles
            ->first(fn (Title $title): bool => $titles->contains('id', $title->id))
            ?->id;

        if ($conflictingTitleId === null) {
            return;
        }

        $title = $titles->firstWhere('id', $conflictingTitleId);

        throw SchedulingConflictException::titleAlreadyAssigned(
            $title === null ? "ID: {$conflictingTitleId}" : (string) $title->name,
        );
    }
}
