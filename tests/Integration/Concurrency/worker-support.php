<?php

declare(strict_types=1);

use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;

/*
 * Shared by the concurrency worker scripts, which are plain scripts and cannot use the Pest helpers.
 */

/** Whether the server picked this process as a deadlock victim: PostgreSQL SQLSTATE 40P01, MySQL error 1213 (SQLSTATE 40001). */
function isDeadlock(Throwable $exception): bool
{
    if ($exception instanceof DeadlockException) {
        return true;
    }

    if (! $exception instanceof QueryException) {
        return false;
    }

    $sqlState = $exception->errorInfo[0] ?? null;

    return $sqlState === '40P01' || ($sqlState === '40001' && ($exception->errorInfo[1] ?? null) === 1213);
}
