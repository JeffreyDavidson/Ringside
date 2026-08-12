<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Lifecycle\LifecycleTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @mixin Model */
trait HasLifecycleTransitions
{
    /** @return MorphMany<LifecycleTransition, $this> */
    public function lifecycleTransitions(): MorphMany
    {
        return $this->morphMany(LifecycleTransition::class, 'subject')
            ->orderBy('effective_at')
            ->orderBy('id');
    }
}
