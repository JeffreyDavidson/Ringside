<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseWrestlerAction
{
    use AsAction;

    /**
     * Suspend a wrestler and make them unavailable for competition.
     *
     * This handles the complete wrestler suspension workflow:
     * - Validates the wrestler can be suspended (currently employed)
     * - Creates a suspension record with the specified start date
     * - Makes the wrestler unavailable for match bookings
     * - May affect tag team bookability if wrestler is in a team
     *
     * @param  Wrestler  $wrestler  The wrestler to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     *
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
        // Validate business rules before proceeding
        $wrestler->ensureCanBeSuspended();

        $suspensionDate = $this->getEffectiveDate($suspensionDate);

        $this->wrestlerRepository->createSuspension($wrestler, $suspensionDate);
    }
}
