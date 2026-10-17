<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\Events\LiveShowStatus;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Models\Events\EventPack;
use App\Queries\Events\EventPackSourceQuery;
use App\Queries\Events\LiveShowSourceQuery;
use App\Services\Events\LiveRunSourceService;

/**
 * @phpstan-import-type Assignment from EventPack
 * @phpstan-import-type Cue from EventPack
 * @phpstan-import-type RunningRow from EventPackSourceQuery
 *
 * @phpstan-type SheetRow array{key: string, label: string, timing: array{planned_at?: ?string, revised_at?: ?string}, cues: list<Cue>}
 * @phpstan-type Sheet array{event: Event, source: string, hasLive: bool, version: int, eventName: string, eventDate: ?string, venue: ?string, timezone: string, needsReview: bool, assignments: list<Assignment>, allAssignments: list<Assignment>, rows: list<SheetRow>, revision: ?string, person: ?int, role: ?string}
 */
class EventProductionViewModel
{
    public function __construct(private readonly EventPackSourceQuery $pack, private readonly LiveShowSourceQuery $live, private readonly LiveRunSourceService $comparison) {}

    /**
     * The production sheet of an event the caller has already authorized for viewProduction.
     *
     * @return Sheet
     */
    public function data(Event $event, ?string $source = null, ?int $person = null, ?string $role = null): array
    {
        $data = $this->pack->get($event);
        $run = EventLiveRun::query()->forEventId($event->id)->first();
        $source ??= $run === null ? 'plan' : 'live';
        if ($source === 'live') {
            $run ??= EventLiveRun::query()->forEventId($event->id)->firstOrFail();
            $assignments = $run->content['production']['assignments'] ?? [];
            $rows = array_map(fn (array $item): array => ['key' => $item['key'], 'label' => $item['label'], 'timing' => $item['timing'], 'cues' => $item['cues'] ?? []], $run->content['items']);
            $name = $run->content['event_name'];
            $date = $run->content['event_date'];
            $venue = $run->content['venue'];
            $zone = $run->content['timezone'];
            $revision = $run->content['revision'];
            $version = $run->pack_version;
            $liveSource = $this->live->get($event, $data);
            $review = $run->status !== LiveShowStatus::Completed
                && ($liveSource === null || $this->comparison->hasMovedOn($run, $liveSource['pack'], $liveSource['fingerprint']));
        } else {
            $assignments = array_values(array_filter($data['content']['production']['assignments'] ?? [], fn (array $assignment): bool => $assignment['active']));
            $cues = $data['content']['production']['cues'] ?? [];
            $rows = array_map(fn (array $item): array => ['key' => $item['key'], 'label' => $item['label'], 'timing' => $item['timing'],
                'cues' => array_values(array_filter($cues, fn (array $cue): bool => $cue['item_key'] === $item['key']))], $data['running']);
            $name = $event->name;
            $date = $event->date?->toIso8601String();
            $venue = $event->venue?->name;
            $zone = $data['content']['timezone'];
            $revision = $data['content']['revision'];
            $version = $data['pack']->version ?? 0;
            $review = $data['needsReview'] || $data['unplaced'] !== [];
        }
        $allAssignments = $assignments;
        $activeIds = array_column($assignments, 'id');
        $assignments = array_values(array_filter($assignments, fn (array $entry): bool => ($person === null || $entry['contact_id'] === $person) && ($role === null || $entry['role'] === $role)));
        foreach ($rows as &$row) {
            $row['cues'] = array_values(array_filter($row['cues'], fn (array $cue): bool => $cue['owner_id'] === null || ! in_array($cue['owner_id'], $activeIds, true)
                || (($person === null || $cue['owner_contact_id'] === $person) && ($role === null || ($cue['role'] ?? $cue['owner_role']) === $role))));
        }
        unset($row);

        return ['event' => $event, 'source' => $source, 'hasLive' => $run !== null, 'version' => $version, 'eventName' => $name, 'eventDate' => $date, 'venue' => $venue,
            'timezone' => $zone, 'needsReview' => $review, 'assignments' => $assignments, 'allAssignments' => $allAssignments,
            'rows' => $rows, 'revision' => $revision, 'person' => $person, 'role' => $role];
    }
}
