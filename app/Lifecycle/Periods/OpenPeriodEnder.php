<?php

declare(strict_types=1);

namespace App\Lifecycle\Periods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

final class OpenPeriodEnder
{
    /**
     * End every open row of a membership or assignment query on the given date.
     *
     * Rows that have already started end on the date. Rows that start after it (for example a stable
     * established with a future start date) are closed on their own start date so history is kept and
     * no period ends before it began.
     *
     * @param  EloquentBuilder<*>|QueryBuilder  $query
     * @param  literal-string  $startColumn
     */
    public static function end(EloquentBuilder|QueryBuilder $query, string $startColumn, string $endColumn, Carbon $date): void
    {
        (clone $query)
            ->whereNull($endColumn)
            ->where($startColumn, '<=', $date)
            ->update([$endColumn => $date]);

        (clone $query)
            ->whereNull($endColumn)
            ->where($startColumn, '>', $date)
            ->distinct()
            ->pluck($startColumn)
            ->each(fn (mixed $start): mixed => (clone $query)
                ->whereNull($endColumn)
                ->where($startColumn, $start)
                ->update([$endColumn => $start]));
    }
}
