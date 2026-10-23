<?php

declare(strict_types=1);

use App\Lifecycle\Matches\MatchSchedulingLock;
use App\Models\Matches\EventMatch;
use Illuminate\Support\Facades\DB;

/**
 * Run an Add*ToMatch action the way its callers do: inside a transaction, with the match and its event set locked.
 *
 * @param  Closure(EventMatch): mixed  $assign  Receives the locked match.
 */
function assignWithinLockedMatch(EventMatch $match, Closure $assign): mixed
{
    return DB::transaction(
        fn (): mixed => $assign(resolve(MatchSchedulingLock::class)->lockMatchWithEventSet($match)),
        attempts: 3,
    );
}
