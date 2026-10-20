<?php

declare(strict_types=1);

namespace App\Lifecycle\Events\LiveItemTransitions;

use App\Exceptions\Events\LiveShowException;
use App\Models\Events\EventLiveRun;
use Illuminate\Support\Carbon;

/**
 * One operation on one live-show item: it checks that the item's current state allows the operation and returns
 * the item as it should be stored afterwards. It never writes; the calling Action locks the run, stamps the reason
 * and saves.
 *
 * @phpstan-import-type Item from EventLiveRun
 */
interface LiveItemTransition
{
    /**
     * @return Item
     *
     * @throws LiveShowException when the item's state, the show day or the given times do not allow the operation
     */
    public function apply(EventLiveRun $run, int $index, ?Carbon $start, ?Carbon $finish): array;
}
