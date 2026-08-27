<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use App\Services\TitleLifecycleService;
use Illuminate\Support\Carbon;

class ReinstateAction
{
    public function __construct(
        private TitleLifecycleService $lifecycle,
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
     */
    public function handle(Title $title, ?Carbon $reinstateDate = null, ?string $notes = null): void
    {
        $this->lifecycle->reinstate($title, $reinstateDate ?? now(), $notes);
    }
}
