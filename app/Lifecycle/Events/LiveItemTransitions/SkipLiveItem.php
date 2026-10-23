<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\LiveItemTransitions;

use App\Enums\Events\LiveItemStatus;
use App\Exceptions\Events\LiveShowException;
use App\Lifecycle\Events\LiveShowDayRequirements;
use App\Models\Events\EventLiveRun;
use Illuminate\Support\Carbon;

/** Skips a pending item on the show day; a running item is finished instead. */
final readonly class SkipLiveItem implements LiveItemTransition
{
    public function __construct(private LiveShowDayRequirements $showDay) {}

    public function apply(EventLiveRun $run, int $index, ?Carbon $start, ?Carbon $finish): array
    {
        $item = $run->content['items'][$index];

        if (LiveItemStatus::from($item['state']) !== LiveItemStatus::Pending) {
            throw LiveShowException::transitionInvalid();
        }

        $this->showDay->ensureShowDay($run);

        return [...$item, 'state' => LiveItemStatus::Skipped->value];
    }
}
