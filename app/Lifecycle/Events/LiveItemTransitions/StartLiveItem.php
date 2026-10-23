<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\LiveItemTransitions;

use App\Enums\Events\LiveItemStatus;
use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveShowDayRequirements;
use App\Lifecycle\Events\LiveShowSourceRequirements;
use App\Models\Events\EventLiveRun;
use Illuminate\Support\Carbon;

/** Starts the next pending item while nothing else runs, on the show day and only while the pack is unchanged. */
final readonly class StartLiveItem implements LiveItemTransition
{
    public function __construct(private LiveShowSourceRequirements $source, private LiveShowDayRequirements $showDay) {}

    public function apply(EventLiveRun $run, int $index, ?Carbon $start, ?Carbon $finish): array
    {
        $items = collect($run->content['items']);
        $item = $run->content['items'][$index];

        if (LiveItemStatus::from($item['state']) !== LiveItemStatus::Pending
            || $items->contains('state', LiveItemStatus::Running->value)
            || ($items->firstWhere('state', LiveItemStatus::Pending->value)['key'] ?? null) !== $item['key']) {
            throw LiveShowException::transitionInvalid();
        }

        if ($this->source->hasChanged($run->event, $run)) {
            throw LiveShowException::reviewRequired();
        }
        $this->showDay->ensureShowDay($run);

        return [...$item, 'state' => LiveItemStatus::Running->value, 'start' => now()->utc()->toIso8601String()];
    }
}
