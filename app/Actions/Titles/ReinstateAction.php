<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Exceptions\Titles\CannotBeReinstatedException;
use App\Lifecycle\TitleLifecycleEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReinstateAction
{
    public function __construct(
        private StartActivityPeriodAction $startActivityPeriod,
        private RecordLifecycleTransitionAction $recordLifecycleTransition,
        private TitleLifecycleEligibility $eligibility,
    ) {}

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
     * @throws CannotBeReinstatedException When title cannot be reinstated due to business rules
     */
    public function handle(Title $title, ?Carbon $reinstateDate = null, ?string $notes = null): void
    {
        $reinstateDate = $reinstateDate ?? now();

        DB::transaction(function () use ($title, $reinstateDate, $notes): void {
            $lockedTitle = Title::query()
                ->whereKey($title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureAllowed($lockedTitle, TitleLifecycleTransition::Reinstate);

            $this->startActivityPeriod->handle($lockedTitle, $reinstateDate, rescheduleFuturePeriod: true);
            $this->recordLifecycleTransition->handle(
                $lockedTitle,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Reinstated,
                $reinstateDate,
                array_filter(['notes' => $notes]),
            );
        });
    }
}
