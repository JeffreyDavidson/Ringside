<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use App\Services\TitleLifecycleService;
use Illuminate\Support\Carbon;

class DebutAction
{
    public function __construct(
        private TitleLifecycleService $lifecycle,
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
        $this->lifecycle->debut($title, $debutDate ?? now(), $notes);
    }
}
