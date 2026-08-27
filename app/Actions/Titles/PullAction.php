<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use App\Services\Titles\TitleLifecycleService;
use Illuminate\Support\Carbon;

class PullAction
{
    public function __construct(
        private TitleLifecycleService $lifecycle,
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
        $this->lifecycle->pull($title, $pullDate ?? now(), $notes);
    }
}
