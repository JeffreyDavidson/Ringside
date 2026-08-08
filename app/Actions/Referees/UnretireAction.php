<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeUnretiredException;
use App\Models\Referees\Referee;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UnretireAction
{
    /**
     * Unretire a retired referee and return them to active officiating.
     *
     * This handles the complete referee unretirement workflow:
     * - Uses StatusTransitionPipeline for consistent unretirement handling
     * - Validates the referee can be unretired (currently retired)
     * - Ends the current retirement period with the specified date
     * - Creates a new employment record starting from the unretirement date
     * - Restores the referee to available status for match assignments
     * - Preserves all historical retirement and employment records
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistent status handling, following the same
     * pattern as other referee actions.
     *
     * @param  Referee  $referee  The referee to unretire
     * @param  Carbon|null  $unretiredDate  The unretirement date (defaults to now)
     * @throws CannotBeUnretiredException When referee cannot be unretired due to business rules
     */
    public function handle(Referee $referee, ?Carbon $unretiredDate = null): void
    {
        $referee->ensureCanBeUnretired();

        $unretiredDate = DateHelper::resolveDate($unretiredDate);

        DB::transaction(function () use ($referee, $unretiredDate): void {
            StatusTransitionPipeline::unretire($referee, $unretiredDate)->execute();

            $referee->employments()->create([
                'started_at' => $unretiredDate,
                'ended_at' => null,
            ]);
        });
    }
}
