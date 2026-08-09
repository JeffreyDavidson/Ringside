<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\Titles\CannotBeRetiredException;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(private readonly RetirementPeriodManager $retirementPeriods) {}

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
     * @throws CannotBeRetiredException When title cannot be retired due to business rules
     */
    public function handle(Title $title, ?Carbon $retirementDate = null): void
    {
        $title->ensureCanBeRetired();

        $retirementDate = $retirementDate ?? now();

        DB::transaction(function () use ($title, $retirementDate): void {
            // Handle title status - active titles need to be pulled before retirement
            if ($title->hasActivityPeriods() && $title->isCurrentlyActive()) {
                $currentActivityPeriod = $title->currentActivityPeriod()->first();
                if ($currentActivityPeriod) {
                    $currentActivityPeriod->update(['ended_at' => $retirementDate]);
                }
            }

            // End current championship if title has an active champion
            $currentChampionship = $title->currentChampionship;
            if ($currentChampionship) {
                $currentChampionship->update(['lost_at' => $retirementDate]);
            }

            $this->retirementPeriods->start($title, $retirementDate);
        });
    }
}
