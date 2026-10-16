<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventProductionData;
use App\Exceptions\Events\EventPackConflictException;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Events\EventProductionRequirements;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Queries\Events\EventPackCardQuery;
use App\Queries\Events\EventPackSourceQuery;
use Illuminate\Support\Facades\DB;

/** @phpstan-import-type Production from EventPack */
class SaveEventProductionAction
{
    public function __construct(private readonly EventPackCardQuery $card, private readonly EventPackSourceQuery $source,
        private readonly EventProductionRequirements $requirements) {}

    /** The submitted plan is checked for shape again inside the save, so it is safe from any caller. */
    public function handle(Event $event, int $version, string $fingerprint, EventProductionData $data, bool $reviewed): void
    {
        DB::transaction(function () use ($event, $version, $fingerprint, $data, $reviewed): void {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            EventMatch::query()->withTrashed()->forEventId($event->id)->orderBy('id')->lockForUpdate()->get();
            $pack = EventPack::query()->where('event_id', $event->id)->lockForUpdate()->first();
            $card = $this->card->get($event);
            if (($pack->version ?? 0) !== $version || ! hash_equals($card['fingerprint'], $fingerprint)) {
                throw RecordChangedException::changed();
            }
            if ($pack !== null && $pack->card_fingerprint !== $fingerprint && ! $reviewed) {
                throw EventPackConflictException::reviewRequired();
            }
            $input = $this->requirements->validatedInput(['notes' => $data->notes, 'assignments' => $data->assignments, 'cues' => $data->cues]);
            $content = $pack->content ?? $this->source->get($event)['content'];
            $keys = [...array_map(fn (array $row): string => 'match:'.$row['id'], $card['rows']),
                ...array_map(fn (array $segment): string => 'segment:'.$segment['id'], $content['segments'])];
            $contacts = CrewContact::query()->where('promotion_id', $event->promotion_id)
                ->whereKey(data_get($input, 'assignments.*.contact_id', []))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $content['production'] = $this->requirements->ensureProduction($input, $content['production'] ?? null, $keys, $contacts);
            $pack ??= new EventPack;
            $pack->event_id = $event->id;
            $pack->version = $version + 1;
            $pack->card_fingerprint = $fingerprint;
            $pack->content = $content;
            $pack->save();
            activity('production')->performedOn($event)->withProperties(['operation' => 'pack-save', 'version' => $pack->version])->log('pack-save');
        }, attempts: 3);
    }
}
