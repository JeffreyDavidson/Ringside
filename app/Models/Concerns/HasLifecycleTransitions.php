<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Builders\Lifecycle\LifecycleTransitionBuilder;
use App\Models\Lifecycle\LifecycleTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @mixin Model */
trait HasLifecycleTransitions
{
    /** @return MorphMany<LifecycleTransition, $this> */
    public function lifecycleTransitions(): MorphMany
    {
        $relation = $this->morphMany(LifecycleTransition::class, 'subject');
        LifecycleTransitionBuilder::constrainChronologically($relation->getQuery());

        return $relation;
    }
}
