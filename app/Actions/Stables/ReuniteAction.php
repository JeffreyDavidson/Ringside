<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\Status\CannotBeActivatedException;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReuniteAction extends BaseStableAction
{
    use AsAction;

    /**
     * Reunite an inactive stable and make it active again.
     *
     * This handles the complete stable reunite workflow:
     * - Validates the stable can be reunited (currently inactive, not retired)
     * - Creates a new activity record to make the stable active
     * - Makes the stable available for new members and storylines
     * - Different from unretirement - this is for inactive stables, not retired ones
     *
     * @param  Stable  $stable  The stable to reunite
     * @param  Carbon|null  $reuniteDate  The reunite date (defaults to now)
     *
     * @throws CannotBeActivatedException When stable cannot be reunited due to business rules
     *
     * @example
     * ```php
     * // Reunite stable immediately
     * ReuniteAction::run($stable);
     *
     * // Reunite with specific date
     * ReuniteAction::run($stable, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Stable $stable, ?Carbon $reuniteDate = null): void
    {
        $stable->ensureCanBeActivated();

        $reuniteDate ??= now();

        DB::transaction(function () use ($stable, $reuniteDate): void {
            $this->stableRepository->createDebut($stable, $reuniteDate);
        });
    }
}
