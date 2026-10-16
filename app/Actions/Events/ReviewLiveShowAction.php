<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Exceptions\Events\LiveShowException;
use App\Exceptions\RecordChangedException;
use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Queries\Events\LiveShowSourceQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReviewLiveShowAction
{
    public function __construct(private readonly LiveShowSourceQuery $source, private readonly LockLiveShowAction $lock,
        private readonly PersistLiveShowAction $persist) {}

    /** @param array<string, string> $removedReasons */
    public function handle(Event $event, int $version, int $packVersion, string $fingerprint, array $removedReasons): void
    {
        DB::transaction(function () use ($event, $version, $packVersion, $fingerprint, $removedReasons): void {
            $event = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $event);
            EventMatch::query()->withTrashed()->forEventId($event->id)->orderBy('id')->lockForUpdate()->get();
            EventPack::query()->where('event_id', $event->id)->lockForUpdate()->first();
            $run = $this->lock->handle($event, $version);
            $source = $this->source->get($run->event);
            if ($source['pack']->version !== $packVersion || ! hash_equals($source['fingerprint'], $fingerprint)
                || ! $this->source->changed($run->event, $run)) {
                throw RecordChangedException::changed();
            }
            $content = $run->content;
            $old = collect($content['items'])->keyBy('key');
            $sourceKeys = array_column($source['content']['items'], 'key');
            $removed = $old->filter(fn (array $item): bool => $item['state'] === 'pending' && ! in_array($item['key'], $sourceKeys, true));
            if (array_diff(array_keys($removedReasons), $removed->keys()->all()) !== []) {
                throw LiveShowException::unknownItem();
            }
            $protected = $old->filter(fn (array $item): bool => $item['state'] !== 'pending')->values()->all();
            foreach ($removed as $key => $item) {
                $reason = $removedReasons[$key] ?? '';
                if (mb_trim($reason) === '' || mb_strlen($reason) > 2000) {
                    throw LiveShowException::reasonRequired();
                }
                $protected[] = [...$item, 'state' => 'skipped', 'reason' => $reason, 'updated_by' => (int) auth()->id()];
            }
            $pending = array_values(array_filter($source['content']['items'], fn (array $item): bool => ($old->get($item['key'])['state'] ?? 'pending') === 'pending'));
            if ($pending === [] && collect($protected)->every(fn (array $item): bool => $item['state'] === 'skipped')) {
                throw LiveShowException::prepareRequired();
            }
            $content['items'] = [...$protected, ...$pending];
            $people = collect($content['roster'])->keyBy('key');
            $roster = [];
            foreach ($source['content']['roster'] as $person) {
                $previous = $people->get($person['key']);
                if ($previous !== null && ($previous['arrived'] !== null || $previous['briefed'] !== null)) {
                    $person = [...$previous, 'assigned' => true, 'matches' => $person['matches'], 'warning' => $person['warning'], ...isset($person['assignments']) ? ['assignments' => $person['assignments'], 'role' => $person['role']] : []];
                }
                $roster[] = $person;
                $people->forget($person['key']);
            }
            foreach ($people as $person) {
                if ($person['arrived'] !== null || $person['briefed'] !== null) {
                    $roster[] = [...$person, 'assigned' => false];
                }
            }
            $content['roster'] = $roster;
            $content['production'] = $source['content']['production'] ?? ['assignments' => []];
            $content['event_date'] = $source['content']['event_date'];
            $content['event_name'] = $source['content']['event_name'];
            $content['venue'] = $source['content']['venue'];
            $content['revision'] = $source['content']['revision'];
            $before = ['pack_version' => $run->pack_version, 'fingerprint' => $run->card_fingerprint];
            $run->pack_version = $packVersion;
            $run->card_fingerprint = $fingerprint;
            $run->content = $content;
            $this->persist->handle($run, 'review', 'show', $before,
                ['pack_version' => $packVersion, 'fingerprint' => $fingerprint, 'skipped' => $removedReasons]);
        }, attempts: 3);
    }
}
