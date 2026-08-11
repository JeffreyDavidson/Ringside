<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReuniteAction
{
    /**
     * Create a new reunite action instance.
     */
    public function __construct(
        protected StartActivityPeriodAction $startActivityPeriodAction,
    ) {}

    /**
     * Reunite an inactive stable and make it active again.
     *
     * This handles the complete stable reunite workflow:
     * - Validates the stable can be reunited (currently inactive, not retired)
     * - Creates a new activity record to make the stable active
     * - Makes the stable available for new members and storylines
     * - Different from establishment - this is for comeback storylines
     *
     * @param  Stable  $stable  The stable to reunite
     * @param  Carbon|null  $reuniteDate  The reunite date (defaults to now)
     * @throws CannotBeReunitedException When stable cannot be reunited due to business rules
     */
    public function handle(Stable $stable, ?Carbon $reuniteDate = null): void
    {
        $reuniteDate ??= now();

        DB::transaction(function () use ($stable, $reuniteDate): void {
            $lockedStable = Stable::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($stable->getKey());

            $lockedStable->ensureCanBeReunited();
            $this->startActivityPeriodAction->handle($lockedStable, $reuniteDate);
        });
    }
}
