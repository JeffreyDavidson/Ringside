<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\Status\CannotBeDebutedException;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class DebutAction extends BaseTitleAction
{
    use AsAction;

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
     *
     * @throws CannotBeDebutedException When title cannot be debuted due to business rules
     *
     * @example
     * ```php
     * // Debut title immediately
     * DebutAction::run($title, null, 'Brand new championship');
     *
     * // Debut with specific date
     * DebutAction::run($title, Carbon::parse('2024-01-01'), 'New era begins');
     * ```
     */
    public function handle(Title $title, ?Carbon $debutDate = null, ?string $notes = null): void
    {
        $title->ensureCanBeDebuted();

        $debutDate = $this->getEffectiveDate($debutDate);

        DB::transaction(function () use ($title, $debutDate, $notes): void {
            // Create the debut record and activate the title for competition
            $this->titleRepository->createDebut($title, $debutDate, $notes);
        });
    }
}
