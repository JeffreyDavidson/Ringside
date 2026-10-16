<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Events\LiveShowSourceRequirements;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PrepareLiveShowAction
{
    public function __construct(private readonly LiveShowSourceRequirements $source) {}

    public function handle(Event $event, int $version, int $packVersion, string $fingerprint): EventLiveRun
    {
        return DB::transaction(function () use ($event, $version, $packVersion, $fingerprint): EventLiveRun {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            EventMatch::query()->withTrashed()->forEventId($event->id)->orderBy('id')->lockForUpdate()->get();
            EventPack::query()->where('event_id', $event->id)->lockForUpdate()->first();
            $run = EventLiveRun::query()->where('event_id', $event->id)->lockForUpdate()->first();
            if ($run !== null || $version !== 0) {
                throw RecordChangedException::changed();
            }
            $source = $this->source->ensureSource($event);
            if ($source['pack']->version !== $packVersion || ! hash_equals($source['fingerprint'], $fingerprint)) {
                throw RecordChangedException::changed();
            }
            if (Promotion::toLocalTime($event->promotion, Carbon::parse($source['content']['event_date']))->startOfDay()->lt(Promotion::toLocalTime($event->promotion, now())->startOfDay())) {
                throw LiveShowException::oldEvent();
            }
            $run = new EventLiveRun;
            $run->event_id = $event->id;
            $run->version = 1;
            $run->pack_version = $packVersion;
            $run->card_fingerprint = $fingerprint;
            $run->status = LiveShowStatus::Prepared;
            $run->content = $source['content'];
            $run->prepared_at = now();
            $run->save();
            activity('live-show')->performedOn($event)->withProperties(['operation' => 'prepare', 'version' => 1])->log('prepare');

            return $run->setRelation('event', $event);
        }, attempts: 3);
    }
}
