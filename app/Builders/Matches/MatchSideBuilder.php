<?php

declare(strict_types=1);

namespace App\Builders\Matches;

use App\Models\Matches\MatchSide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Builder<MatchSide>
 */
class MatchSideBuilder extends Builder
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainToPositionOrder(Builder $query): void
    {
        $query->orderBy('position');
    }
}
