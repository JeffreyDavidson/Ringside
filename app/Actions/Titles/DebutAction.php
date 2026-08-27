<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Titles\TitleLifecycleTransition;
use App\Exceptions\Titles\CannotBeDebutedException;
use App\Lifecycle\TitleLifecycleEligibility;
use App\Models\Titles\Title;
use App\Services\TitleActivityPeriodService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DebutAction
{
    public function __construct(
        private TitleLifecycleEligibility $eligibility,
        private TitleActivityPeriodService $activityPeriods,
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
     * @throws CannotBeDebutedException When title cannot be debuted due to business rules
     */
    public function handle(Title $title, ?Carbon $debutDate = null, ?string $notes = null): void
    {
        $debutDate = $debutDate ?? now();

        DB::transaction(function () use ($title, $debutDate, $notes): void {
            $lockedTitle = Title::query()
                ->whereKey($title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureAllowed($lockedTitle, TitleLifecycleTransition::Debut);

            $this->activityPeriods->start($lockedTitle, $debutDate, LifecycleTransitionType::Debuted, $notes);
        });
    }
}
