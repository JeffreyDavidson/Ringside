<?php

declare(strict_types=1);

namespace App\Builders\Lifecycle;

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Retirement;
use App\Models\Lifecycle\Suspension;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of ActivityPeriod|Employment|Injury|Retirement|Suspension
 *
 * @extends Builder<TModel>
 */
class LifecyclePeriodBuilder extends Builder
{
    /**
     * @template TRelatedModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     */
    public static function constrainToOpen(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /**
     * @template TRelatedModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     */
    public static function constrainToEnded(Builder $query): void
    {
        $query->whereNotNull('ended_at');
    }

    /**
     * @template TRelatedModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     */
    public static function constrainToCurrent(Builder $query): void
    {
        self::constrainToOpen($query);
        $query->where('started_at', '<=', now());
    }

    /**
     * @template TRelatedModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     */
    public static function constrainToScheduled(Builder $query): void
    {
        self::constrainToOpen($query);
        $query->where('started_at', '>', now());
    }

    /**
     * @template TRelatedModel of Model
     *
     * @param  Builder<TRelatedModel>  $query
     */
    public static function constrainToActiveOn(Builder $query, DateTimeInterface $date): void
    {
        $query
            ->where('started_at', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $date);
            });
    }
}
