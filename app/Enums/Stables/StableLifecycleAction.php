<?php

declare(strict_types=1);

namespace App\Enums\Stables;

/**
 * Lifecycle actions offered on the stable detail page. Merge, split and reunite stay unwired.
 */
enum StableLifecycleAction: string
{
    case Establish = 'establish';
    case Disband = 'disband';
    case Retire = 'retire';
    case Unretire = 'unretire';

    public function ability(): string
    {
        return $this->value;
    }
}
