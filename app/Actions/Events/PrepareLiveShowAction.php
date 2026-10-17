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
use App\Services\Promotions\PromotionTimeZoneService;
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
            EventPack::query()->forEventId($event->id)->lockForUpdate()->first();
            $run = EventLiveRun::query()->forEventId($event->id)->lockForUpdate()->first();
            if ($run !== null || $version !== 0) {
                throw RecordChangedException::changed();
            }
            $source = $this->source->ensureSource($event);
            if ($source['pack']->version !== $packVersion || ! hash_equals($source['fingerprint'], $fingerprint)) {
                throw RecordChangedException::changed();
            }
            if (PromotionTimeZoneService::toLocalTime($event->promotion, Carbon::parse($source['content']['event_date']))->startOfDay()->lt(PromotionTimeZoneService::toLocalTime($event->promotion, now())->startOfDay())) {
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
