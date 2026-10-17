<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Builders\Concerns\FiltersByEventId;
use App\Models\Events\EventPack;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of EventPack
 *
 * @extends Builder<TModel>
 */
class EventPackBuilder extends Builder
{
    use FiltersByEventId;
}
