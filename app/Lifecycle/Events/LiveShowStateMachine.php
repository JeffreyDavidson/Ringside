<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Models\Events\EventLiveRun;

/**
 * The live show's reachable status rules: the status a run starts in, whether it is still open, and whether it can
 * be completed.
 *
 * It reads the run and refuses with LiveShowException; it never locks or writes. The Actions lock the run
 * (LockLiveShowAction) and set the status themselves: PrepareLiveShowAction creates the run as Prepared, starting an
 * item sets Running, and CompleteLiveShowAction sets Completed. There is no moves table: the lock already refuses
 * a completed show, so no other move can be refused.
 */
final readonly class LiveShowStateMachine
{
    /** The status a newly prepared run starts in. */
    public function initial(): LiveShowStatus
    {
        return LiveShowStatus::Prepared;
    }

    /**
     * Refuse any change to a completed show.
     *
     * @throws LiveShowException
     */
    public function ensureOpen(EventLiveRun $run): void
    {
        if ($run->status === LiveShowStatus::Completed) {
            throw LiveShowException::completed();
        }
    }

    /**
     * Refuse completing a show that still has a pending or running item.
     *
     * @throws LiveShowException
     */
    public function ensureCanComplete(EventLiveRun $run): void
    {
        $unfinished = collect($run->content['items'])->contains(
            fn (array $item): bool => in_array($item['state'], [LiveItemStatus::Pending->value, LiveItemStatus::Running->value], true),
        );

        if ($unfinished) {
            throw LiveShowException::unfinished();
        }
    }
}
