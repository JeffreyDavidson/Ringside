<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\Status\CannotBeActivatedException;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Reinstate an inactive title and make it active again.
     *
     * This handles the complete title reinstatement workflow:
     * - Validates the title can be reinstated (currently inactive, not retired)
     * - Ends the current inactive period and creates new active period
     * - Updates the title status to active
     * - Makes the title available for championship matches and defenses
     * - Different from unretirement - this is for inactive titles, not retired ones
     *
     * @param  Title  $title  The title to reinstate
     * @param  Carbon|null  $reinstateDate  The reinstatement date (defaults to now)
     * @param  string|null  $notes  Optional notes about the reinstatement
     *
     * @throws CannotBeActivatedException When title cannot be reinstated due to business rules
     *
     * @example
     * ```php
     * // Reinstate title immediately
     * ReinstateAction::run($title, null, 'New storyline beginning');
     *
     * // Reinstate with specific date
     * ReinstateAction::run($title, Carbon::parse('2024-01-01'), 'Return after rebrand');
     * ```
     */
    public function handle(Title $title, ?Carbon $reinstateDate = null, ?string $notes = null): void
    {
        $title->ensureCanBeReinstated();

        $reinstateDate = $this->getEffectiveDate($reinstateDate);

        DB::transaction(function () use ($title, $reinstateDate, $notes): void {
            // Create reinstatement record to reactivate the title for competition
            $this->titleRepository->createReinstatement($title, $reinstateDate, $notes);
        });
    }
}
