<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\LiveShowChangeData;
use App\Models\Events\EventLiveRun;

/** Persist and audit one already-validated live operation within its owning transaction. */
class PersistLiveShowAction
{
    public function handle(EventLiveRun $run, LiveShowChangeData $change): void
    {
        $run->version++;
        $run->save();
        // Private production instructions stay in the protected run, outside the generic event audit.
        $before = $change->before;
        $after = $change->after;
        unset($before['cues'], $after['cues']);
        activity('live-show')->performedOn($run->event)->withProperties([
            'operation' => $change->operation, 'target' => $change->target, 'version' => $run->version,
            'before' => $before, 'after' => $after, 'reason' => $change->reason,
        ])->log($change->operation);
    }
}
