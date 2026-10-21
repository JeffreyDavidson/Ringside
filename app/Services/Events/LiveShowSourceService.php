<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Enums\Crew\CrewRole;
use App\Enums\Events\LiveItemStatus;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\ValueObjects\ProductionPlan;
use Illuminate\Support\Collection;

/**
 * @phpstan-import-type Content from EventLiveRun
 * @phpstan-import-type PackSource from EventPackSourceService
 *
 * @phpstan-type Source array{pack: EventPack, fingerprint: string, content: Content}
 */
class LiveShowSourceService
{
    public function __construct(private readonly EventPackSourceService $pack) {}

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

        $production = ProductionPlan::fromArray($data['content']['production'] ?? null);
        $assignments = $production->activeAssignments();
        $crew = collect($assignments)->groupBy(fn (array $assignment): string => 'crew:'.$assignment['contact_id'])
            ->map(fn (Collection $group, string $key): array => ['key' => $key, 'name' => $group->firstOrFail()['name'],
                'role' => $group->map(fn (array $entry): string => CrewRole::from($entry['role'])->label())->unique()->implode(', '),
                'matches' => [], 'warning' => false, 'assigned' => true, 'arrived' => null, 'briefed' => null, 'updated_by' => null,
                'assignments' => array_values($group->all())]);

        return ['pack' => $data['pack'], 'fingerprint' => $data['card']['fingerprint'], 'content' => [
            'event_date' => $event->date->toIso8601String(), 'event_name' => $event->name,
            'venue' => $event->venue?->name, 'timezone' => $data['content']['timezone'], 'revision' => $data['content']['revision'],
            'items' => array_map(fn (array $row): array => [...$row, 'timezone' => $data['content']['timezone'], 'state' => LiveItemStatus::Pending->value, 'start' => null,
                'finish' => null, 'reason' => null, 'updated_by' => null, 'cues' => $production->cuesFor($row['key'])], $data['running']),
            'roster' => [...array_map(fn (array $person): array => [...$person, 'assigned' => true,
                'arrived' => null, 'briefed' => null, 'updated_by' => null], $data['card']['roster']), ...$crew->values()->all()],
            'production' => ['assignments' => $assignments],
        ]];
    }
}
