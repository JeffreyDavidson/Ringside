<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\Events\LiveShowStatus;
use App\Models\Events\Event;
use App\Models\Events\EventLiveRun;
use App\Queries\Events\LiveShowSourceQuery;
use App\Services\Events\LiveShowTimingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;

/**
 * @phpstan-import-type Content from EventLiveRun
 * @phpstan-import-type Item from EventLiveRun
 * @phpstan-import-type Source from LiveShowSourceQuery
 *
 * @phpstan-type Screen array{event: Event, run: ?EventLiveRun, source: ?Source, sourceError: ?string, needsReview: bool, removed: list<Item>, current: ?Item, next: ?Item, complete: bool}
 */
class LiveShowViewModel
{
    public function __construct(private readonly LiveShowSourceQuery $source, private readonly LiveShowTimingService $timing) {}

    /** @return Screen */
    public function data(Event $event): array
    {
        $event->load(['promotion', 'venue']);
        $run = EventLiveRun::query()->where('event_id', $event->id)->first();
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
        $needsReview = $run !== null && ! $complete && ($source === null || $source['pack']->version !== $run->pack_version
            || ! hash_equals($run->card_fingerprint, $source['fingerprint']));
        $items = collect($run?->content['items'] ?? []);
        $sourceKeys = array_column($source['content']['items'] ?? [], 'key');

        return ['event' => $event, 'run' => $run, 'source' => $source, 'sourceError' => $sourceError,
            'needsReview' => $needsReview, 'complete' => $complete,
            'removed' => array_values($items->filter(fn (array $item): bool => $item['state'] === 'pending' && ! in_array($item['key'], $sourceKeys, true))->all()),
            'current' => $items->firstWhere('state', 'running'), 'next' => $items->firstWhere('state', 'pending')];
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

    public function actual(?string $instant, string $timezone): string
    {
        return $instant === null ? __('live-show.not_recorded') : Carbon::parse($instant)->setTimezone($timezone)->format('M j H:i:s P');
    }

    /** @param Item $item */
    public function comparison(array $item): string
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
