<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use Illuminate\Support\Facades\Gate;

/** Re-read the owned event and run inside the caller's transaction before any live mutation. */
class LockLiveShowAction
{
    public function handle(Event $event, int $version): EventLiveRun
    {
        $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
        Gate::authorize('update', $event);
        $event->load('promotion');
        $run = EventLiveRun::query()->where('event_id', $event->id)->lockForUpdate()->firstOrFail();
        if ($run->version !== $version) {
            throw RecordChangedException::changed();
        }
        if ($run->status === LiveShowStatus::Completed) {
            throw LiveShowException::completed();
        }

        return $run->setRelation('event', $event);
    }
}
