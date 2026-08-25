<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\IndividualSuspensionService;
use Illuminate\Support\Carbon;

class SuspendAction
{
    public function __construct(
        private readonly IndividualSuspensionService $suspension,
    ) {}

    /**
     * Suspend a wrestler and make them unavailable for competition.
     *
     * This handles the complete wrestler suspension workflow:
     * - Validates the wrestler can be suspended
     * - Creates the suspension record through the shared lifecycle component
     * - Makes the wrestler unavailable for match bookings
     * - May affect tag team bookability if wrestler is in a team
     *
     * @param  Wrestler  $wrestler  The wrestler to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     * @throws CannotBeSuspendedException When wrestler cannot be suspended due to business rules
     */
    public function handle(Wrestler $wrestler, ?Carbon $suspensionDate = null): void
    {
        $this->suspension->suspend($wrestler, $suspensionDate ?? now());
    }
}
