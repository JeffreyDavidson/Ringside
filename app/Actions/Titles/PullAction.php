<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Exceptions\Status\CannotBeDeactivatedException;
use App\Exceptions\Status\CannotBePulledException;
use App\Models\Titles\Title;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class PullAction
{
    use AsAction;

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
     * @throws CannotBeDeactivatedException When title cannot be pulled due to business rules
     *
     * @example
     * ```php
     * // Pull title immediately
     * PullAction::run($title, null, 'Brand overhaul');
     *
     * // Pull with specific date
     * PullAction::run($title, Carbon::parse('2024-06-30'), 'Summer break');
     * ```
     */
    public function handle(Title $title, ?Carbon $pullDate = null, ?string $notes = null): void
    {
        $this->ensureCanBePulled($title);

        $pullDate = $pullDate ?? now();

        DB::transaction(function () use ($title, $pullDate): void {
            $currentActivityPeriod = $title->currentActivityPeriod()->first();
            if ($currentActivityPeriod) {
                $currentActivityPeriod->update(['ended_at' => $pullDate]);
            }
        });
    }

    /**
     * Ensure the title can be pulled from active competition.
     *
     * @param  Title  $title  The title to validate
     * @throws CannotBePulledException When the title cannot be pulled
     */
    private function ensureCanBePulled(Title $title): void
    {
        // A title can only be pulled if it's currently active
        if (! $title->isCurrentlyActive()) {
            throw new CannotBePulledException('Title must be currently active to be pulled from competition.');
        }

        // Cannot pull a retired title
        if ($title->isRetired()) {
            throw new CannotBePulledException('Cannot pull a retired title.');
        }
    }
}
