<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Builders\Concerns\FiltersByEventId;
use App\Models\Events\EventPublication;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of EventPublication
 *
 * @extends Builder<TModel>
 */
class EventPublicationBuilder extends Builder
{
    use FiltersByEventId;
}
