<?php

declare(strict_types=1);

namespace App\Builders\Events;

use App\Builders\Concerns\FiltersByEventId;
use App\Models\Events\EventSponsorCommitment;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of EventSponsorCommitment
 *
 * @extends Builder<TModel>
 */
class EventSponsorCommitmentBuilder extends Builder
{
    use FiltersByEventId;
}
