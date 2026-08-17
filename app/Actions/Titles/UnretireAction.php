<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Exceptions\Titles\CannotBeUnretiredException;
use App\Lifecycle\RetirementPeriodManager;
use App\Lifecycle\TitleLifecycleEligibility;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    public function __construct(
        private readonly RetirementPeriodManager $retirementPeriods,
        private readonly TitleLifecycleEligibility $eligibility,
    ) {}

    /**
     * Unretire a retired title and make it available for future competition.
     *
     * This handles the complete title unretirement workflow:
     * - Validates the title can be unretired (currently retired)
     * - Ends the current retirement period with the specified date
     * - Makes the title available for future championship competition
     * - Does not automatically make the title active (requires separate debut/reinstate)
     * - Preserves all historical retirement and championship records
     * - Different from reinstatement - this reverses retirement, not inactive status
     *
     * @param  Title  $title  The title to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @throws CannotBeUnretiredException When title cannot be unretired due to business rules
     */
    public function handle(Title $title, ?Carbon $unretiredDate = null): void
    {
        $unretiredDate = $unretiredDate ?? now();

        DB::transaction(function () use ($title, $unretiredDate): void {
            $lockedTitle = Title::query()
                ->withTrashed()
                ->whereKey($title->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->eligibility->ensureCanUnretire($lockedTitle);
            $this->retirementPeriods->end($lockedTitle, $unretiredDate, LifecycleTransitionType::Unretired);
        });
    }
}
