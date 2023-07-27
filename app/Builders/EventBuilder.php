<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModelClass>
 */
class EventBuilder extends Builder
{
    /**
     * Scope a query to include scheduled events.
     */
    public function scheduled(): self
    {
        $this->where('status', EventStatus::SCHEDULED)->whereNotNull('date');

        return $this;
    }

    /**
     * Scope a query to include past events.
     */
    public function past(): self
    {
        $this->where('status', EventStatus::PAST)->where('date', '<', now()->toDateString());

        return $this;
    }
}
