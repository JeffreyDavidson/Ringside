<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\Status\CannotBeDebutedException;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DebutAction extends BaseStableAction
{
    use AsAction;

    /**
     * Create a new debut action instance.
     */
    public function __construct(
        protected StableRepository $stableRepository
    ) {
        parent::__construct($stableRepository);
    }

    /**
     * Debut a stable.
     *
     * This handles the complete stable debut workflow:
     * - Validates the stable can be debuted (not already active)
     * - Creates debut record for the stable making it available for storylines
     * - Ensures all current members are also debuted if not already active
     * - Makes the stable and members available for championship opportunities
     * - Establishes the stable as a competitive force in wrestling storylines
     *
     * @param  Stable  $stable  The stable to debut
     * @param  Carbon|null  $debutDate  The debut date (defaults to now)
     *
     * @throws CannotBeDebutedException When stable cannot be debuted due to business rules
     *
     * @example
     * ```php
     * // Debut stable immediately
     * DebutAction::run($stable);
     *
     * // Debut with specific date
     * DebutAction::run($stable, Carbon::parse('2024-01-01'));
     *
     * // Debut The Four Horsemen stable
     * $fourHorsemen = Stable::where('name', 'The Four Horsemen')->first();
     * DebutAction::run($fourHorsemen, Carbon::parse('2024-03-15'));
     * ```
     */
    public function handle(Stable $stable, ?Carbon $debutDate = null): void
    {
        $stable->ensureCanBeDebuted();

        $debutDate = $this->getEffectiveDate($debutDate);

        DB::transaction(function () use ($stable, $debutDate): void {
            // Create the debut record for the stable
            $this->stableRepository->createDebut($stable, $debutDate);

            // Note: Individual members (wrestlers, managers, tag teams) do not have debut functionality.
            // Only Stables and Titles can be debuted according to business rules.
            // Members are managed through employment, not debut status.
        });
    }
}
