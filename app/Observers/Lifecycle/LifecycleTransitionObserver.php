<?php

declare(strict_types=1);

namespace App\Observers\Lifecycle;

use App\Models\Lifecycle\LifecycleTransition;
use LogicException;

class LifecycleTransitionObserver
{
    public function updating(LifecycleTransition $lifecycleTransition): never
    {
        throw new LogicException('Lifecycle transition records are immutable.');
    }

    public function deleting(LifecycleTransition $lifecycleTransition): never
    {
        throw new LogicException('Lifecycle transition records are immutable.');
    }
}
