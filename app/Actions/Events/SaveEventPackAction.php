<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventPackData;
use App\Exceptions\Events\EventPackConflictException;
use App\Exceptions\RecordChangedException;
use App\Lifecycle\Events\EventCardLock;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Services\Events\EventPackCardService;
use Illuminate\Support\Facades\DB;

class SaveEventPackAction
{
    public function __construct(private readonly EventPackCardService $card, private readonly EventCardLock $lock) {}

    public function handle(Event $event, int $version, string $fingerprint, EventPackData $data, bool $reviewed): void
    {
        DB::transaction(function () use ($event, $version, $fingerprint, $data, $reviewed): void {
            // Card assignment actions also lock the owning event before changing its matches.
            ['event' => $event, 'pack' => $pack] = $this->lock->lock($event->id);
            $card = $this->card->get($event);

            if (($pack->version ?? 0) !== $version || ! hash_equals($card['fingerprint'], $fingerprint)) {
                throw RecordChangedException::changed();
            }

            if ($pack !== null && $pack->card_fingerprint !== $fingerprint && ! $reviewed) {
                throw EventPackConflictException::reviewRequired();
            }

            // Retain removed-match notes and omitted entries; identity is never inferred from card position.
            $content = [
                'timezone' => $data->timezone,
                'doors_at' => $data->doorsAt,
                'revision' => $data->revision,
                'master' => $data->master,
                'notes' => $data->notes,
                'follow_up' => $data->followUp,
                'matches' => array_values(collect($pack?->content['matches'] ?? [])->keyBy('match_id')
                    ->replace(collect($data->matches)->keyBy('match_id'))->all()),
                'segments' => $data->segments,
            ];
            $sponsorTasks = $data->sponsorTasks ?? $pack->content['sponsor_tasks'] ?? null;

            if ($sponsorTasks !== null) {
                $content['sponsor_tasks'] = $sponsorTasks;
            }

            if (isset($pack?->content['production'])) {
                $content['production'] = $pack->content['production'];
            }

            $pack ??= new EventPack;
            $pack->event_id = $event->id;
            $pack->version = $version + 1;
            $pack->card_fingerprint = $fingerprint;
            $pack->fill(['content' => $content]);
            $pack->save();
        }, attempts: 3);
    }
}
