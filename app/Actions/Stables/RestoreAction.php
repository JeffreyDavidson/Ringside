<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction
{
    use AsAction;

    /**
     * Create a new restore action instance.
     */
    public function __construct(
        protected ReuniteAction $reuniteAction
    ) {}

    /**
     * Restore a soft-deleted stable.
     *
     * This handles the complete stable restoration workflow:
     * - Validates the stable can be restored (business rules and member availability)
     * - Restores the soft-deleted stable record from trash
     * - Optionally reunites available former members for immediate storyline viability
     * - Preserves all historical member relationships and match history
     * - Makes the stable available for new storylines and operations
     *
     * RESTORATION OPTIONS:
     * - Basic restoration: Just undeletes the stable record (inactive state)
     * - With reunion: Automatically reunites available former members (active state)
     * - Member checking: Validates former member availability before restoration
     *
     * @param  Stable  $stable  The soft-deleted stable to restore
     * @param  bool  $reuniteMembers  Whether to automatically reunite available former members
     * @param  bool  $requireFormerMembers  Whether to require available former members for restoration
     * @param  Carbon|null  $restorationDate  The restoration date (defaults to now)
     *
     * @example
     * ```php
     * // Basic restoration (stable remains inactive)
     * $deletedStable = Stable::onlyTrashed()->find(1);
     * RestoreAction::run($deletedStable);
     *
     * // Restore with automatic reunion
     * RestoreAction::run($deletedStable, reuniteMembers: true);
     *
     * // Restore without requiring former members
     * RestoreAction::run($deletedStable, requireFormerMembers: false);
     *
     * // Restore with specific date
     * RestoreAction::run($deletedStable, restorationDate: Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(
        Stable $stable,
        bool $reuniteMembers = false,
        bool $requireFormerMembers = true,
        ?Carbon $restorationDate = null
    ): void {
        $stable->ensureCanBeRestored($requireFormerMembers);

        $restorationDate = $restorationDate ?? now();

        DB::transaction(function () use ($stable, $reuniteMembers, $restorationDate): void {
            // Restore the soft-deleted stable record
            $stable->restore();

            if ($reuniteMembers) {
                // Get available former members for reunion
                $availableFormerMembers = $stable->getAvailableFormerMembers();

                if ($availableFormerMembers->isNotEmpty()) {
                    // Use ReuniteAction to bring back available former members
                    $this->reuniteAction->handle($stable, $restorationDate);
                }
                // If no members available but reunion requested, stable stays inactive
                // This allows for manual member addition later

            }

            // Note: If not reuniting members, stable remains in inactive state
            // This allows for manual configuration before reactivation
        });
    }
}
