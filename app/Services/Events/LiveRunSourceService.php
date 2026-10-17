<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;

/** Compares a prepared live run with the saved pack and card it was prepared from. */
class LiveRunSourceService
{
    /**
     * Whether the saved pack is gone or its version differs, or the card fingerprint differs, from what the run was
     * prepared from. Callers add their own conditions, such as a source that can no longer be built.
     */
    public function hasMovedOn(EventLiveRun $run, ?EventPack $pack, string $cardFingerprint): bool
    {
        return ! $pack instanceof EventPack || $pack->version !== $run->pack_version
            || ! hash_equals($run->card_fingerprint, $cardFingerprint);
    }
}
