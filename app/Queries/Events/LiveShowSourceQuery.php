<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Enums\Crew\CrewRole;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;

/**
 * @phpstan-import-type Content from EventLiveRun
 * @phpstan-import-type PackSource from EventPackSourceQuery
 *
 * @phpstan-type Source array{pack: EventPack, fingerprint: string, content: Content}
 */
class LiveShowSourceQuery
{
    public function __construct(private readonly EventPackSourceQuery $pack) {}

    /**
     * The live show's source, or null while the event has no date, no saved pack, a pack that no longer matches
     * the card, segments without a match to follow, or nothing to run.
     *
     * @param  PackSource|null  $packSource  the event's pack source when the caller has already read it
     * @return Source|null
     */
    public function get(Event $event, ?array $packSource = null): ?array
    {
        $data = $packSource ?? $this->pack->get($event);
        if ($event->date === null || $data['pack'] === null || $data['needsReview'] || $data['unplaced'] !== [] || $data['running'] === []) {
            return null;
        }

        $assignments = array_values(array_filter($data['content']['production']['assignments'] ?? [], fn (array $assignment): bool => $assignment['active']));
        $crew = [];
        foreach ($assignments as $assignment) {
            $key = 'crew:'.$assignment['contact_id'];
            $crew[$key] ??= ['key' => $key, 'name' => $assignment['name'], 'role' => '', 'matches' => [], 'warning' => false,
                'assigned' => true, 'arrived' => null, 'briefed' => null, 'updated_by' => null, 'assignments' => []];
            $crew[$key]['assignments'][] = $assignment;
            $crew[$key]['role'] = implode(', ', array_unique(array_map(fn (array $entry): string => CrewRole::from($entry['role'])->label(), $crew[$key]['assignments'])));
        }
        $cues = $data['content']['production']['cues'] ?? [];

        return ['pack' => $data['pack'], 'fingerprint' => $data['card']['fingerprint'], 'content' => [
            'event_date' => $event->date->toIso8601String(), 'event_name' => $event->name,
            'venue' => $event->venue?->name, 'timezone' => $data['content']['timezone'], 'revision' => $data['content']['revision'],
            'items' => array_map(fn (array $row): array => [...$row, 'timezone' => $data['content']['timezone'], 'state' => 'pending', 'start' => null,
                'finish' => null, 'reason' => null, 'updated_by' => null, 'cues' => array_values(array_filter($cues, fn (array $cue): bool => $cue['item_key'] === $row['key']))], $data['running']),
            'roster' => [...array_map(fn (array $person): array => [...$person, 'assigned' => true,
                'arrived' => null, 'briefed' => null, 'updated_by' => null], $data['card']['roster']), ...array_values($crew)],
            'production' => ['assignments' => $assignments],
        ]];
    }
}
