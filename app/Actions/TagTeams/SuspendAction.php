<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Lifecycle\SuspensionPeriodManager;
use App\Models\TagTeams\TagTeam;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SuspendAction
{
    public function __construct(
        private readonly SuspensionPeriodManager $suspensionPeriods,
        private readonly SuspendCurrentMembersAction $suspendCurrentMembers,
    ) {}

    /**
     * Suspend a tag team.
     *
     * This handles the complete tag team suspension workflow:
     * - Validates the tag team can be suspended (currently employed, not already suspended)
     * - Creates the suspension record through the shared lifecycle component
     * - Automatically cascades suspension to eligible wrestlers and managers
     * - Temporarily removes the tag team from active competition
     * - Maintains employment status while restricting availability
     * - Ensures all members are properly suspended to maintain team suspension integrity
     *
     * @param  TagTeam  $tagTeam  The tag team to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     */
    public function handle(TagTeam $tagTeam, ?Carbon $suspensionDate = null): void
    {
        $tagTeam->ensureCanBeSuspended();

        $suspensionDate = DateHelper::resolveDate($suspensionDate);

        DB::transaction(function () use ($tagTeam, $suspensionDate): void {
            $this->suspensionPeriods->start($tagTeam, $suspensionDate);
            $this->suspendCurrentMembers->handle($tagTeam, $suspensionDate);
        });
    }
}
