<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Builders\Concerns\FiltersByEventId;
use App\Models\Events\EventLiveRun;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of EventLiveRun
 *
 * @extends Builder<TModel>
 */
class EventLiveRunBuilder extends Builder
{
    use FiltersByEventId;
}
