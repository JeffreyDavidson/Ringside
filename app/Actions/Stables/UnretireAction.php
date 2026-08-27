<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Models\Roster\Stables\Stable;
use App\Services\Roster\Stables\StableUnretirementService;
use Illuminate\Support\Carbon;

class UnretireAction
{
    /**
     * Create a new unretire action instance.
     */
    public function __construct(
        protected StartActivityPeriodAction $startActivityPeriodAction,
        protected StableUnretirementService $unretirement,
    ) {}

    /**
     * Unretire a retired stable and optionally make it active again.
     */
    public function handle(
        Stable $stable,
        ?Carbon $unretiredDate = null,
        bool $establishImmediately = true,
        bool $requireFormerMembers = true
    ): void {
        $this->unretirement->unretire($stable, $unretiredDate ?? now(), $requireFormerMembers, function (Stable $lockedStable, Carbon $effectiveDate) use ($establishImmediately): void {
            if ($establishImmediately) {
                $this->startActivityPeriodAction->handle($lockedStable, $effectiveDate);
            }
        });
    }
}
