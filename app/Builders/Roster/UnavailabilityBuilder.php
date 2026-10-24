<?php

declare(strict_types=1);

namespace App\Builders\Roster;

use App\Models\Roster\Availability\Unavailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Unavailability
 *
 * @extends Builder<TModel>
 */
class UnavailabilityBuilder extends Builder
{
    public function active(): static
    {
        return $this->whereNull('cancelled_at');
    }

    public function covering(string $date): static
    {
        self::constrainToCovering($this, $date);

        return $this;
    }

    /**
     * Keep uncancelled entries covering the date. Static so a relationship constraint closure the
     * static analyser types as a plain Eloquent builder can apply the same clause.
     *
     * @template TRelatedModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     */
    public static function constrainToCovering(Builder $query, string $date): void
    {
        $query->whereNull('cancelled_at')->where('starts_on', '<=', $date)->where('ends_on', '>=', $date);
    }
}
