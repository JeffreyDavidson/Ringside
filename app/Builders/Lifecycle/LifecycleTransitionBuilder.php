<?php

declare(strict_types=1);

namespace App\Builders\Lifecycle;

use App\Models\Lifecycle\LifecycleTransition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Builder<LifecycleTransition>
 */
class LifecycleTransitionBuilder extends Builder
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainChronologically(Builder $query): void
    {
        $query->orderBy('effective_at')
            ->orderBy('id');
    }
}
