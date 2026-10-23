<?php

declare(strict_types=1);

namespace App\ViewModels\Events;

use App\Enums\Events\LiveItemStatus;
use App\Enums\Events\LiveShowStatus;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Queries\Events\EventVenueQuery;
use App\Services\Events\LiveRunSourceService;
use App\Services\Events\LiveShowSourceService;
use App\Services\Events\LiveShowTimingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;

/**
 * @phpstan-import-type Content from EventLiveRun
 * @phpstan-import-type Item from EventLiveRun
 * @phpstan-import-type Source from LiveShowSourceService
 *
 * @phpstan-type Times array{start: string, finish: string}
 * @phpstan-type Attendance array{arrived: string, briefed: string}
 * @phpstan-type Screen array{event: Event, run: ?EventLiveRun, source: ?Source, sourceError: ?string, needsReview: bool, removed: list<Item>, current: ?Item, next: ?Item, complete: bool, runComparisons: array<string, string>, sourceComparisons: array<string, string>, itemTimes: array<string, Times>, attendance: array<string, Attendance>, refreshedLabel: ?string, savedLabel: ?string}
 */
class LiveShowViewModel
{
    public function __construct(private readonly LiveShowSourceService $source, private readonly LiveShowTimingService $timing, private readonly LiveRunSourceService $comparison, private readonly EventVenueQuery $venues) {}

    /**
     * @param  string  $refreshedAt  when the page last refreshed, as an ISO 8601 instant
     * @return Screen
     */
    public function data(Event $event, string $refreshedAt = ''): array
    {
        $event->load('promotion');
        $event->setRelation('venue', $this->venues->get($event));
        $run = EventLiveRun::query()->forEventId($event->id)->first();
        $source = null;
        $sourceError = null;
        $complete = $run?->status === LiveShowStatus::Completed;

        if (! $complete) {
            $source = $this->source->get($event);
            $sourceError = $source === null ? Lang::string('live-show.prepare_required') : null;
        }

        if (Gate::denies('viewProduction', $event)) {
            if ($run !== null) {
                $run = clone $run;
                $run->content = $this->withoutProduction($run->content);
            }

            if ($source !== null) {
                $source['content'] = $this->withoutProduction($source['content']);
            }
        }

        $needsReview = $run !== null && ! $complete && ($source === null || $this->comparison->hasMovedOn($run, $source['pack'], $source['fingerprint']));
        $items = collect($run?->content['items'] ?? []);
        $sourceKeys = array_column($source['content']['items'] ?? [], 'key');
        $zone = $run->content['timezone'] ?? 'UTC';
        $itemTimes = $items->mapWithKeys(fn (array $item): array => [$item['key'] => ['start' => $this->actual($item['start'], $zone), 'finish' => $this->actual($item['finish'], $zone)]])->all();
        $attendance = collect($run->content['roster'] ?? [])
            ->mapWithKeys(fn (array $person): array => [$person['key'] => ['arrived' => $this->actual($person['arrived'], $zone), 'briefed' => $this->actual($person['briefed'], $zone)]])
            ->all();

        return ['event' => $event, 'run' => $run, 'source' => $source, 'sourceError' => $sourceError,
            'needsReview' => $needsReview, 'complete' => $complete,
            'runComparisons' => $this->comparisons($run?->content['items'] ?? []),
            'sourceComparisons' => $this->comparisons($source['content']['items'] ?? []),
            'itemTimes' => $itemTimes, 'attendance' => $attendance,
            'refreshedLabel' => $run === null ? null : $this->actual($refreshedAt, $zone),
            'savedLabel' => $run === null ? null : $this->actual($run->updated_at->toIso8601String(), $zone),
            'removed' => array_values($items->filter(fn (array $item): bool => $item['state'] === LiveItemStatus::Pending->value && ! in_array($item['key'], $sourceKeys, true))->all()),
            'current' => $items->firstWhere('state', LiveItemStatus::Running->value), 'next' => $items->firstWhere('state', LiveItemStatus::Pending->value)];
    }

    /** @param Content $content
     * @return Content
     */
    private function withoutProduction(array $content): array
    {
        unset($content['production']);
        $content['items'] = array_map(function (array $item): array {
            unset($item['cues']);

            return $item;
        }, $content['items']);
        $content['roster'] = array_values(array_filter($content['roster'], fn (array $person): bool => ! str_starts_with($person['key'], 'crew:')));

        return $content;
    }

    private function actual(?string $instant, string $timezone): string
    {
        return $instant === null ? __('live-show.not_recorded') : Carbon::parse($instant)->setTimezone($timezone)->format('M j H:i:s P');
    }

    /**
     * How each item's actual start compares with its plan, keyed by item key.
     *
     * @param  list<Item>  $items
     * @return array<string, string>
     */
    private function comparisons(array $items): array
    {
        return collect($items)->mapWithKeys(fn (array $item): array => [$item['key'] => $this->comparison($item)])->all();
    }

    /** @param Item $item */
    private function comparison(array $item): string
    {
        $planned = $this->timing->wallTime($item['timing']['revised_at'] ?? $item['timing']['planned_at'] ?? null, $item['timezone']);

        if (! $planned instanceof Carbon) {
            return __('live-show.no_plan');
        }

        if ($item['start'] === null) {
            return __('live-show.planned', ['time' => $planned->setTimezone($item['timezone'])->format('M j H:i P')]);
        }

        $minutes = (int) round($planned->diffInMinutes(Carbon::parse($item['start']), absolute: false));

        return match (true) {
            $minutes > 0 => __('live-show.late', ['minutes' => $minutes]),
            $minutes < 0 => __('live-show.early', ['minutes' => abs($minutes)]),
            default => __('live-show.on_time'),
        };
    }
}
