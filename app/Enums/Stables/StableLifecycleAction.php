<?php

declare(strict_types=1);

namespace App\Enums\Stables;

/**
 * Lifecycle actions offered on the stable detail page. Merge, split and reunite open a modal because they need input.
 */
enum StableLifecycleAction: string
{
    case Establish = 'establish';
    case Disband = 'disband';
    case Retire = 'retire';
    case Unretire = 'unretire';
    case Merge = 'merge';
    case Split = 'split';
    case Reunite = 'reunite';

    public function ability(): string
    {
        return $this->value;
    }
}
