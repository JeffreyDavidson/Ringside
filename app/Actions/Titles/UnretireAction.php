<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use App\Services\Titles\TitleRetirementService;
use Illuminate\Support\Carbon;

class UnretireAction
{
    public function __construct(
        private readonly TitleRetirementService $retirement,
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
     */
    public function handle(Title $title, ?Carbon $unretiredDate = null): void
    {
        $this->retirement->unretire($title, $unretiredDate ?? now());
    }
}
