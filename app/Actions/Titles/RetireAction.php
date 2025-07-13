<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RetireAction extends BaseTitleAction
{
    use AsAction;

    /**
     * Retire a title and permanently end its championship lineage.
     *
     * This handles the complete title retirement workflow:
     * - Validates the title can be retired (currently active or inactive)
     * - Ends active status if currently active
     * - Creates retirement record to permanently retire the championship
     * - Makes the title unavailable for future competition permanently
     * - Preserves championship history and lineage for legacy purposes
     * - Ends any current championship reigns associated with the title
     *
     * @param  Title  $title  The title to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     *
     * @throws CannotBeRetiredException When title cannot be retired due to business rules
     *
     * @example
     * ```php
     * // Retire title immediately
     * RetireAction::run($title);
     *
     * // Retire with specific date
     * RetireAction::run($title, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(Title $title, ?Carbon $retirementDate = null): void
    {
        $title->ensureCanBeRetired();

        $retirementDate = $this->getEffectiveDate($retirementDate);

        DB::transaction(function () use ($title, $retirementDate): void {
            // Handle title status - active titles need to be pulled before retirement
            if ($title->hasActivityPeriods() && $title->isCurrentlyActive()) {
                // Pull the title from active competition before retiring
                $this->titleRepository->pull($title, $retirementDate);
            }

            // Create the retirement record to permanently end the title's lineage
            $this->titleRepository->createRetirement($title, $retirementDate);
        });
    }
}
