<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Exceptions\Titles\CannotBeRetiredException;
use App\Lifecycle\RetirementPeriodManager;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly EndActivityPeriodAction $endActivityPeriod,
        private readonly RetirementPeriodManager $retirementPeriods,
    ) {}

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
        $retirementDate = $retirementDate ?? now();
        $operationalDate = $retirementDate->isFuture() ? now() : $retirementDate;

        DB::transaction(function () use ($title, $retirementDate, $operationalDate): void {
            $lockedTitle = Title::query()
                ->lockForUpdate()
                ->findOrFail($title->getKey());

            $lockedTitle->ensureCanBeRetired();

            if ($lockedTitle->hasActivityPeriods() && $lockedTitle->isCurrentlyActive()) {
                $this->endActivityPeriod->handle($lockedTitle, $operationalDate);
            }

            // End current championship if title has an active champion
            $currentChampionship = $lockedTitle->currentChampionship;
            if ($currentChampionship) {
                $currentChampionship->update(['lost_at' => $retirementDate]);
            }

            $this->retirementPeriods->start($lockedTitle, $retirementDate);
        });
    }
}
