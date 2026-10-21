<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\LiveItemTransitions;

use App\Enums\Events\LiveItemStatus;
use App\Exceptions\Events\LiveShowException;
use App\Models\Events\EventLiveRun;
use Illuminate\Support\Carbon;

/**
 * Replaces the actual times of a running or finished item. A running item keeps no finish, a finished item needs
 * one, neither may lie in the future, and the item may not overlap the recorded times of the items around it.
 */
final readonly class CorrectLiveItemTimes implements LiveItemTransition
{
    public function apply(EventLiveRun $run, int $index, ?Carbon $start, ?Carbon $finish): array
    {
        $item = $run->content['items'][$index];
        $state = LiveItemStatus::from($item['state']);
        if (! in_array($state, [LiveItemStatus::Running, LiveItemStatus::Finished], true) || ! $start instanceof Carbon
            || $start->gt(now()) || ($state === LiveItemStatus::Finished && ! $finish instanceof Carbon)
            || ($state === LiveItemStatus::Running && $finish instanceof Carbon)
            || ($finish instanceof Carbon && ($finish->lt($start) || $finish->gt(now())))) {
            throw LiveShowException::invalidTimes();
        }
        foreach ($run->content['items'] as $position => $other) {
            if (($position < $index && $other['finish'] !== null && Carbon::parse($other['finish'])->gt($start))
                || ($position > $index && $other['start'] !== null && (! $finish instanceof Carbon || Carbon::parse($other['start'])->lt($finish)))) {
                throw LiveShowException::invalidTimes();
            }
        }

        return [...$item, 'start' => $start->copy()->utc()->toIso8601String(), 'finish' => $finish?->copy()->utc()->toIso8601String()];
    }
}
