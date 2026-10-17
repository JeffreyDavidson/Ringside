<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Builders\Concerns\FiltersByEventId;
use App\Models\Events\EventBudget;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of EventBudget
 *
 * @extends Builder<TModel>
 */
class EventBudgetBuilder extends Builder
{
    use FiltersByEventId;
}
