<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Actions\Lifecycle\StartActivityPeriodAction;
use App\Exceptions\Titles\CannotBeDebutedException;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;

class DebutAction
{
    public function __construct(private StartActivityPeriodAction $startActivityPeriod) {}

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
        $title->ensureCanBeDebuted();

        $debutDate = $debutDate ?? now();

        $this->startActivityPeriod->handle($title, $debutDate, rescheduleFuturePeriod: true);
    }
}
