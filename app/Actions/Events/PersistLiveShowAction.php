<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\LiveShowChangeData;
use App\Models\Events\EventLiveRun;
use App\Models\Users\User;

/** Persist and audit one already-validated live operation within its owning transaction. */
class PersistLiveShowAction
{
    public function handle(EventLiveRun $run, LiveShowChangeData $change, User $actor): void
    {
        $run->version++;
        $run->save();
        // Private production instructions stay in the protected run, outside the generic event audit.
        $before = $change->before;
        $after = $change->after;
        unset($before['cues'], $after['cues']);

        activity('live-show')
            ->causedBy($actor)
            ->performedOn($run->event)
            ->withProperties([
                'operation' => $change->operation,
                'target' => $change->target,
                'version' => $run->version,
                'before' => $before,
                'after' => $after,
                'reason' => $change->reason,
            ])
            ->log($change->operation);
    }
}
