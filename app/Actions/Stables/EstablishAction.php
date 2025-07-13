<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\Status\CannotBeActivatedException;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class EstablishAction extends BaseStableAction
{
    use AsAction;

    /**
     * Establish a stable and make it active.
     *
     * This handles the complete stable establishment workflow:
     * - Validates the stable can be established (currently unactivated)
     * - Creates an establishment record with the specified date
     * - Makes the stable available for storylines and championship opportunities
     * - Activates the stable's debut period
     *
     * @param  Stable  $stable  The stable to establish
     * @param  Carbon|null  $activationDate  The establishment date (defaults to now)
     *
     * @throws CannotBeActivatedException When stable cannot be established due to business rules
     *
     * @example
     * ```php
     * // Establish stable immediately
     * $stable = Stable::where('name', 'The Shield')->first();
     * EstablishAction::run($stable);
     *
     * // Establish with specific date
     * EstablishAction::run($stable, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Stable $stable, ?Carbon $activationDate = null): void
    {
        $stable->ensureCanBeActivated();

        $activationDate = $this->getEffectiveDate($activationDate);

        DB::transaction(function () use ($stable, $activationDate): void {
            // Create establishment record
            $this->stableRepository->createActivity($stable, $activationDate);
        });
    }
}
