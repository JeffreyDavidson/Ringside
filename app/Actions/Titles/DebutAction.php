<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Actions\Lifecycle\RecordLifecycleTransitionAction;
use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Lifecycle\Titles\TitleLifecycleEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DebutAction
{
    public function __construct(
        private readonly TitleLifecycleEligibility $eligibility,
        private readonly StartActivityPeriodAction $startActivityPeriod,
        private readonly RecordLifecycleTransitionAction $recordLifecycleTransition,
    ) {}

    /**
     * Debut a title and make it available for championship competition.
     *
     * This handles the complete title debut workflow:
     * - Validates the title can be debuted (not already active, not retired)
     * - Creates active status period to track the debut
     * - Updates the title status to active
     * - Makes the title available for championship matches and defenses
     * - Establishes the beginning of the title's competitive lineage
     *
     * @param  Title  $title  The title to debut
     * @param  Carbon|null  $debutDate  The debut date (defaults to now)
     * @param  string|null  $notes  Optional notes about the debut
     */
    public function handle(Title $title, ?Carbon $debutDate = null, ?string $notes = null): void
    {
        $date = $debutDate ?? now();

        DB::transaction(function () use ($title, $date, $notes): void {
            $lockedTitle = $title->refreshForUpdate();
            $this->eligibility->ensureAllowed($lockedTitle, TitleLifecycleTransition::Debut);
            $this->startActivityPeriod->handle($lockedTitle, $date);
            $this->recordLifecycleTransition->handle(
                $lockedTitle,
                LifecycleDimension::Activity,
                LifecycleTransitionType::Debuted,
                $date,
                array_filter(['notes' => $notes]),
            );
        });
    }
}
