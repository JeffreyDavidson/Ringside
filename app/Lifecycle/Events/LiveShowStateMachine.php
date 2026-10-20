<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Models\Events\EventLiveRun;

/**
 * The moves a live show run may make between its three stored statuses.
 *
 * It reads the run and refuses with LiveShowException; it never locks or writes. The Actions lock the run
 * (LockLiveShowAction) and set the status themselves: PrepareLiveShowAction creates the run as Prepared, starting an
 * item sets Running, and CompleteLiveShowAction sets Completed.
 */
final readonly class LiveShowStateMachine
{
    /**
     * The allowed moves, by the status the run is in (the key is the status value). Anything not listed is refused.
     * Starting a later item keeps a running show Running; a completed show makes no move.
     */
    private const array MOVES = [
        'prepared' => [LiveShowStatus::Running, LiveShowStatus::Completed],
        'running' => [LiveShowStatus::Running, LiveShowStatus::Completed],
        'completed' => [],
    ];

    /** The status a newly prepared run starts in. */
    public function initial(): LiveShowStatus
    {
        return LiveShowStatus::Prepared;
    }

    public function canMove(LiveShowStatus $from, LiveShowStatus $to): bool
    {
        return in_array($to, self::MOVES[$from->value], true);
    }

    /**
     * Refuse a move the table does not allow.
     *
     * @throws LiveShowException
     */
    public function ensureCanMove(EventLiveRun $run, LiveShowStatus $to): void
    {
        if (! $this->canMove($run->status, $to)) {
            throw LiveShowException::completed();
        }
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
     * Refuse completing a show that cannot move to Completed, or one that still has a pending or running item.
     *
     * @throws LiveShowException
     */
    public function ensureCanComplete(EventLiveRun $run): void
    {
        $this->ensureCanMove($run, LiveShowStatus::Completed);

        $unfinished = collect($run->content['items'])->contains(
            fn (array $item): bool => in_array($item['state'], [LiveItemStatus::Pending->value, LiveItemStatus::Running->value], true),
        );

        if ($unfinished) {
            throw LiveShowException::unfinished();
        }
    }
}
