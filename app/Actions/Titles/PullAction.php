<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Actions\Lifecycle\EndActivityPeriodAction;
use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Lifecycle\Titles\TitleLifecycleEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PullAction
{
    public function __construct(
        private readonly TitleLifecycleEligibility $eligibility,
        private readonly EndActivityPeriodAction $endActivityPeriod,
        private readonly RecordLifecycleTransitionAction $recordLifecycleTransition,
    ) {}

    /**
     * Pull a title from active competition and make it inactive.
     *
     * This handles the complete title pull workflow:
     * - Validates the title can be pulled (currently active)
     * - Ends the current active status period
     * - Creates new inactive status period
     * - Updates the title status to inactive
     * - Makes the title unavailable for new championship matches
     * - Preserves championship history and allows for future reinstatement
     * - Different from retirement - this is temporary deactivation, not permanent
     *
     * @param  Title  $title  The title to pull
     * @param  Carbon|null  $pullDate  The pull date (defaults to now)
     * @param  string|null  $notes  Optional notes about the pull
     */
    public function handle(Title $title, ?Carbon $pullDate = null, ?string $notes = null): void
    {
        $date = $pullDate ?? now();

        DB::transaction(function () use ($title, $date, $notes): void {
            $lockedTitle = $title->refreshForUpdate();
            $this->eligibility->ensureAllowed($lockedTitle, TitleLifecycleTransition::Pull);
            $this->endActivityPeriod->handle($lockedTitle, $date);
            $this->recordLifecycleTransition->handle(
                $lockedTitle,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Pulled,
                $date,
                array_filter(['notes' => $notes]),
            );
        });
    }
}
