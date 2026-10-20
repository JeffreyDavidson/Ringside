<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\LiveItemTransitions;

use App\Enums\Events\LiveItemStatus;
use App\Exceptions\Events\LiveShowException;
use App\Models\Events\EventLiveRun;
use Illuminate\Support\Carbon;

/** Finishes the running item now; its recorded start must already have passed. */
final readonly class FinishLiveItem implements LiveItemTransition
{
    public function apply(EventLiveRun $run, int $index, ?Carbon $start, ?Carbon $finish): array
    {
        $item = $run->content['items'][$index];
        if (LiveItemStatus::from($item['state']) !== LiveItemStatus::Running || $item['start'] === null || Carbon::parse($item['start'])->gt(now())) {
            throw LiveShowException::transitionInvalid();
        }

        return [...$item, 'state' => LiveItemStatus::Finished->value, 'finish' => now()->utc()->toIso8601String()];
    }
}
