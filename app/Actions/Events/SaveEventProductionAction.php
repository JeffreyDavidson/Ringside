<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventProductionData;
use App\Exceptions\Events\EventPackConflictException;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Events\EventCardLock;
use App\Lifecycle\Events\EventProductionRequirements;
use App\Models\Crew\CrewContact;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Users\User;
use App\Services\Events\EventPackCardService;
use App\Services\Events\EventPackSourceService;
use Illuminate\Support\Facades\DB;

/** @phpstan-import-type Production from EventPack */
class SaveEventProductionAction
{
    public function __construct(
        private readonly EventPackCardService $card,
        private readonly EventPackSourceService $source,
        private readonly EventProductionRequirements $requirements,
        private readonly EventCardLock $lock,
    ) {}

    /** The submitted plan is checked for shape again inside the save, so it is safe from any caller. */
    public function handle(Event $event, int $version, string $fingerprint, EventProductionData $data, bool $reviewed, User $actor): void
    {
        DB::transaction(function () use ($event, $version, $fingerprint, $data, $reviewed, $actor): void {
            ['event' => $event, 'pack' => $pack] = $this->lock->lock($event->id);
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
            $contacts = CrewContact::query()
                ->where('promotion_id', $event->promotion_id)
                ->whereKey(data_get($input, 'assignments.*.contact_id', []))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $content['production'] = $this->requirements->ensureProduction($input, $content['production'] ?? null, $keys, $contacts);
            $pack ??= new EventPack;
            $pack->event_id = $event->id;
            $pack->version = $version + 1;
            $pack->card_fingerprint = $fingerprint;
            $pack->fill(['content' => $content]);
            $pack->save();

            activity('production')
                ->causedBy($actor)
                ->performedOn($event)
                ->withProperties([
                    'operation' => 'pack-save',
                    'version' => $pack->version,
                ])
                ->log('pack-save');
        }, attempts: 3);
    }
}
