<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use App\Services\StableEstablishmentService;
use Illuminate\Support\Carbon;

class EstablishAction
{
    public function __construct(
        protected StableEstablishmentService $establishment,
    ) {}

    /**
     * Establish a stable and make it active.
     */
    public function handle(
        Stable $stable,
        ?Carbon $activationDate = null,
        ?Carbon $endDate = null,
    ): ActivityPeriod {
        return $this->establishment->establish($stable, $activationDate ?? now(), $endDate);
    }
}
