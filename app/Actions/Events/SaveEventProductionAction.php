<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Exceptions\Events\EventPackConflictException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Queries\Events\EventPackCardQuery;
use App\Services\Events\EventProductionValidationService;
use App\ViewModels\EventPackViewModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** @phpstan-import-type Production from EventPack */
class SaveEventProductionAction
{
    public function __construct(private readonly EventPackCardQuery $card, private readonly EventPackViewModel $pack,
        private readonly EventProductionValidationService $validator) {}

    /** @param array<string, mixed> $input */
    public function handle(Event $event, int $version, string $fingerprint, array $input, bool $reviewed): void
    {
        DB::transaction(function () use ($event, $version, $fingerprint, $input, $reviewed): void {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('updateProduction', $event);
            EventMatch::query()->withTrashed()->forEventId($event->id)->orderBy('id')->lockForUpdate()->get();
            $pack = EventPack::query()->where('event_id', $event->id)->lockForUpdate()->first();
            $card = $this->card->get($event);
            if (($pack->version ?? 0) !== $version || ! hash_equals($card['fingerprint'], $fingerprint)) {
                throw RecordChangedException::changed();
            }
            if ($pack !== null && $pack->card_fingerprint !== $fingerprint && ! $reviewed) {
                throw EventPackConflictException::reviewRequired();
            }
            $content = $pack->content ?? $this->pack->data($event)['content'];
            $keys = [...array_map(fn (array $row): string => 'match:'.$row['id'], $card['rows']),
                ...array_map(fn (array $segment): string => 'segment:'.$segment['id'], $content['segments'])];
            $content['production'] = $this->validator->validate($event, $input, $content['production'] ?? null, $keys);
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
