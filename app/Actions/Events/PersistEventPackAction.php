<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPack;

/**
 * Write the next version of an event's pack. Runs inside the caller's transaction, after the card lock and the version
 * and fingerprint checks.
 *
 * @phpstan-import-type Content from EventPack
 */
class PersistEventPackAction
{
    /** @param  Content  $content */
    public function handle(Event $event, ?EventPack $pack, int $version, string $fingerprint, array $content): EventPack
    {
        $pack ??= new EventPack;
        $pack->event_id = $event->id;
        $pack->version = $version + 1;
        $pack->card_fingerprint = $fingerprint;
        $pack->fill(['content' => $content]);
        $pack->save();

        return $pack;
    }
}
