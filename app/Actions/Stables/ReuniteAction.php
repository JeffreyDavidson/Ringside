<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\Roster\Stables\CannotBeReunitedException;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReuniteAction
{
    use AsAction;

    /**
     * Create a new reunite action instance.
     */
    public function __construct(
        protected EstablishAction $establishAction
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
        $stable->ensureCanBeReunited();

        $reuniteDate ??= now();

        $this->establishAction->handle($stable, $reuniteDate);
    }
}
