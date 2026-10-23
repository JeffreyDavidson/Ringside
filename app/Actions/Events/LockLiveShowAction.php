<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Exceptions\RecordChangedException;
use App\Lifecycle\Events\LiveShowStateMachine;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;

/** Re-read the owned event and run inside the caller's transaction before any live mutation. */
class LockLiveShowAction
{
    public function __construct(private readonly LiveShowStateMachine $states) {}

    public function handle(Event $event, int $version): EventLiveRun
    {
        $event = Event::query()
            ->whereKey($event->id)
            ->lockForUpdate()
            ->firstOrFail();
        $event->load('promotion');

        $run = EventLiveRun::query()
            ->forEventId($event->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($run->version !== $version) {
            throw RecordChangedException::changed();
        }

        $this->states->ensureOpen($run);

        return $run->setRelation('event', $event);
    }
}
