<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Events\EventCardLock;
use App\Lifecycle\Events\LiveShowSourceRequirements;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Services\Promotions\PromotionTimeZoneService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PrepareLiveShowAction
{
    public function __construct(private readonly LiveShowSourceRequirements $source, private readonly EventCardLock $card) {}

    public function handle(Event $event, int $version, int $packVersion, string $fingerprint): EventLiveRun
    {
        return DB::transaction(function () use ($event, $version, $packVersion, $fingerprint): EventLiveRun {
            ['event' => $event, 'run' => $run] = $this->card->lock($event->id);
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
