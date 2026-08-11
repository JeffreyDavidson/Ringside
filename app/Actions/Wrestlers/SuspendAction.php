<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Lifecycle\SuspensionPeriodManager;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SuspendAction
{
    public function __construct(private readonly SuspensionPeriodManager $suspensionPeriods) {}

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
        $suspensionDate = DateHelper::resolveDate($suspensionDate);

        DB::transaction(function () use ($wrestler, $suspensionDate): void {
            $lockedWrestler = Wrestler::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($wrestler->getKey());

            $lockedWrestler->ensureCanBeSuspended();

            $this->suspensionPeriods->start($lockedWrestler, $suspensionDate);
        });
    }
}
