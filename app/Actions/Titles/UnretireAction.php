<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction
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

        $unretiredDate = $unretiredDate ?? now();

        DB::transaction(function () use ($title, $unretiredDate): void {
            $currentRetirement = $title->currentRetirement()->first();
            if ($currentRetirement) {
                $currentRetirement->update(['ended_at' => $unretiredDate]);
            }
        });
    }
}
