<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\LiveItemTransitions;

use App\Enums\Events\LiveItemStatus;
use App\Exceptions\Events\LiveShowException;
use App\Models\Events\EventLiveRun;
use Illuminate\Support\Carbon;

/**
 * Reopens a finished item as running or a skipped item as pending, keeping its start. Only while nothing else runs
 * and no later item has started.
 */
final readonly class ReopenLiveItem implements LiveItemTransition
{
    public function apply(EventLiveRun $run, int $index, ?Carbon $start, ?Carbon $finish): array
    {
        $items = $run->content['items'];
        $item = $items[$index];
        $state = LiveItemStatus::from($item['state']);
        if (! in_array($state, [LiveItemStatus::Finished, LiveItemStatus::Skipped], true)
            || collect($items)->contains('state', LiveItemStatus::Running->value)
            || collect(array_slice($items, $index + 1))->contains(fn (array $later): bool => $later['start'] !== null)) {
            throw LiveShowException::transitionInvalid();
        }

        return [
            ...$item,
            'state' => $state === LiveItemStatus::Finished ? LiveItemStatus::Running->value : LiveItemStatus::Pending->value,
            'finish' => null,
        ];
    }
}
