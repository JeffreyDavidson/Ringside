<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Exceptions\Events\EventPackConflictException;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Queries\Events\EventPackCardQuery;
use Illuminate\Support\Facades\DB;

/** @phpstan-import-type Content from EventPack */
class SaveEventPackAction
{
    public function __construct(private readonly EventPackCardQuery $card) {}

    /** @param Content $content */
    public function handle(Event $event, int $version, string $fingerprint, array $content, bool $reviewed): void
    {
        DB::transaction(function () use ($event, $version, $fingerprint, $content, $reviewed): void {
            // Card assignment actions also lock the owning event before changing its matches.
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            EventMatch::query()->withTrashed()->forEventId($event->id)->orderBy('id')->lockForUpdate()->get();
            $pack = EventPack::query()->where('event_id', $event->id)->lockForUpdate()->first();
            $card = $this->card->get($event);
            if (($pack->version ?? 0) !== $version || ! hash_equals($card['fingerprint'], $fingerprint)) {
                throw EventPackConflictException::stale();
            }
            if ($pack !== null && $pack->card_fingerprint !== $fingerprint && ! $reviewed) {
                throw EventPackConflictException::reviewRequired();
            }
            // Retain removed-match notes and omitted entries; identity is never inferred from card position.
            $content['matches'] = array_values(collect($pack?->content['matches'] ?? [])->keyBy('match_id')
                ->replace(collect($content['matches'])->keyBy('match_id'))->all());
            $pack ??= new EventPack;
            $pack->event_id = $event->id;
            $pack->version = $version + 1;
            $pack->card_fingerprint = $fingerprint;
            $pack->content = $content;
            $pack->save();
        }, attempts: 3);
    }
}
