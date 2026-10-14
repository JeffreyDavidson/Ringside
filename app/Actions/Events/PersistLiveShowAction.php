<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Events\EventLiveRun;

/** Persist and audit one already-validated live operation within its owning transaction. */
class PersistLiveShowAction
{
    /** @param array<string, mixed> $before
     * @param  array<string, mixed>  $after
     */
    public function handle(EventLiveRun $run, string $operation, string $target, array $before, array $after, ?string $reason = null): void
    {
        $run->version++;
        $run->save();
        // Private production instructions stay in the protected run, outside the generic event audit.
        unset($before['cues'], $after['cues']);
        activity('live-show')->performedOn($run->event)->withProperties([
            'operation' => $operation, 'target' => $target, 'version' => $run->version,
            'before' => $before, 'after' => $after, 'reason' => $reason,
        ])->log($operation);
    }
}
