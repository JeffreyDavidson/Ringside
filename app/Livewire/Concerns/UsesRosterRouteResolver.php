<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Support\RosterResourceRouteResolver;

trait UsesRosterRouteResolver
{
    protected RosterResourceRouteResolver $routeResolver;

    public function boot(RosterResourceRouteResolver $routeResolver): void
    {
        $this->routeResolver = $routeResolver;
    }
}
