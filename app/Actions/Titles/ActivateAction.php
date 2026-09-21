<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Lifecycle\Periods\RetirementPeriodManager;
use App\Lifecycle\Titles\TitleLifecycleEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Activate action for titles.
 *
 * This handles both unretiring and debuting titles to make them active.
 * Use DebutAction for new code that only needs to debut non-retired titles.
 */
class ActivateAction
{
    public function __construct(
        private readonly TitleLifecycleEligibility $eligibility,
        private readonly StartActivityPeriodAction $startActivityPeriod,
        private readonly RecordLifecycleTransitionAction $recordLifecycleTransition,
        private readonly RetirementPeriodManager $retirementPeriods,
    ) {}

    /**
     * Activate a title.
     *
     * @param  Title  $title  The title to activate
     * @param  Carbon|null  $activationDate  The activation date (defaults to now)
     */
    public function handle(Title $title, ?Carbon $activationDate = null): void
    {
        $date = $activationDate ?? now();

        DB::transaction(function () use ($title, $date): void {
            $lockedTitle = $title->refreshForUpdate();

            if ($lockedTitle->currentRetirement()->exists()) {
                $this->eligibility->ensureAllowed($lockedTitle, TitleLifecycleTransition::Unretire);
                $this->retirementPeriods->end($lockedTitle, $date, LifecycleTransitionType::Unretired);
            }

            $transition = $lockedTitle->activityPeriods()->exists()
                ? TitleLifecycleTransition::Reinstate
                : TitleLifecycleTransition::Debut;
            $lifecycleTransition = $transition === TitleLifecycleTransition::Reinstate
                ? LifecycleTransitionType::Reinstated
                : LifecycleTransitionType::Debuted;

            $this->eligibility->ensureAllowed($lockedTitle, $transition);
            $this->startActivityPeriod->handle(
                $lockedTitle,
                $date,
                rescheduleFuturePeriod: $transition === TitleLifecycleTransition::Reinstate,
            );
            $this->recordLifecycleTransition->handle(
                $lockedTitle,
                LifecycleDimension::Activity,
                $lifecycleTransition,
                $date,
            );
        });
    }
}
