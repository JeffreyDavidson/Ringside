<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction
{
    use AsAction;

    /**
     * Suspend a wrestler and make them unavailable for competition.
     *
     * This handles the complete wrestler suspension workflow using StatusTransitionPipeline:
     * - Validates the wrestler can be suspended through pipeline validation
     * - Uses StatusTransitionPipeline to properly create suspension record
     * - Maintains transaction boundaries and error handling through pipeline
     * - Makes the wrestler unavailable for match bookings
     * - May affect tag team bookability if wrestler is in a team
     *
     * ARCHITECTURAL PATTERN:
     * Uses StatusTransitionPipeline for consistency with other entity suspension operations
     * and proper status transition management.
     *
     * @param  Wrestler  $wrestler  The wrestler to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     * @throws CannotBeSuspendedException When wrestler cannot be suspended due to business rules
     *
     * @example
     * ```php
     * // Suspend wrestler immediately
     * SuspendAction::run($wrestler);
     *
     * // Suspend with specific start date
     * SuspendAction::run($wrestler, Carbon::parse('2024-01-15'));
     * ```
     */
    public function handle(Wrestler $wrestler, ?Carbon $suspensionDate = null): void
    {
        $wrestler->ensureCanBeSuspended();

        $suspensionDate = DateHelper::resolveDate($suspensionDate);

        StatusTransitionPipeline::suspend($wrestler, $suspensionDate)->execute();
    }
}
