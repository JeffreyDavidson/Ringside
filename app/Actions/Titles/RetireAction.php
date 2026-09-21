<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Titles\ChampionshipReignManager;
use App\Lifecycle\Titles\TitleLifecycleEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RetireAction
{
    public function __construct(
        private readonly EndActivityPeriodAction $endActivityPeriod,
        private readonly ChampionshipReignManager $championshipReigns,
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly TitleLifecycleEligibility $eligibility,
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
     */
    public function handle(Title $title, ?Carbon $retirementDate = null): void
    {
        $date = $retirementDate ?? now();
        $operationalDate = $date->isFuture() ? now() : $date;

        DB::transaction(function () use ($title, $date, $operationalDate): void {
            $lockedTitle = $title->refreshForUpdate();
            $this->eligibility->ensureAllowed($lockedTitle, TitleLifecycleTransition::Retire);

            if ($lockedTitle->activityPeriods()->exists() && $lockedTitle->currentActivityPeriod()->exists()) {
                $this->endActivityPeriod->handle($lockedTitle, $operationalDate);
            }

            $this->championshipReigns->endCurrentReign($lockedTitle, $date);
            $this->retirementPeriods->start($lockedTitle, $date, LifecycleTransitionType::Retired);
        });
    }
}
