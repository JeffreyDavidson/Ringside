<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseTitleAction
{
    use AsAction;

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
     *
     * @throws CannotBeUnretiredException When title cannot be unretired due to business rules
     *
     * @example
     * ```php
     * // Unretire title immediately
     * UnretireAction::run($title);
     *
     * // Unretire with specific date
     * UnretireAction::run($title, Carbon::parse('2024-01-01'));
     * ```
     */
    public function handle(Title $title, ?Carbon $unretiredDate = null): void
    {
        $title->ensureCanBeUnretired();

        $unretiredDate = $this->getEffectiveDate($unretiredDate);

        DB::transaction(function () use ($title, $unretiredDate): void {
            // End the current retirement record
            $this->titleRepository->endRetirement($title, $unretiredDate);

            // Note: Title is now available for competition but requires separate debut/reinstate action
            // to become active. This preserves the distinction between retired->available and available->active.
        });
    }
}
