<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Roster\Stables\Stable;
use App\Services\StableReuniteService;
use Illuminate\Support\Carbon;

class ReuniteAction
{
    /**
     * Create a new reunite action instance.
     */
    public function __construct(
        protected StableReuniteService $reunite,
    ) {}

    /**
     * Reunite an inactive stable and make it active again.
     */
    public function handle(Stable $stable, ?Carbon $reuniteDate = null): void
    {
        $this->reunite->reunite($stable, $reuniteDate ?? now());
    }
}
