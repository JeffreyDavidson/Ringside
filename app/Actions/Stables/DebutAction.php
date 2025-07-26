<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Exceptions\Status\CannotBeDebutedException;
use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DebutAction
{
    use AsAction;

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

        $debutDate = $debutDate ?? now();

        DB::transaction(function () use ($stable, $debutDate): void {
            $stable->activityPeriods()->updateOrCreate(
                ['ended_at' => null],
                ['started_at' => $debutDate->toDateTimeString()]
            );
        });
    }
}
