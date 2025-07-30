<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\SuspendAction as ManagersSuspendAction;
use App\Actions\Wrestlers\SuspendAction as WrestlersSuspendAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction
{
    use AsAction;

    /**
     * Create a new suspend action instance.
     */
    public function __construct(
        protected WrestlersSuspendAction $wrestlersSuspendAction,
        protected ManagersSuspendAction $managersSuspendAction
    ) {}

    /**
     * Suspend a tag team.
     *
     * This handles the complete tag team suspension workflow:
     * - Validates the tag team can be suspended (currently employed, not already suspended)
     * - Creates a suspension record with the specified start date
     * - Suspends all current wrestlers and managers with the team
     * - Temporarily removes the tag team from active competition
     * - Maintains employment status while restricting availability
     * - Ensures all members are properly suspended to maintain team suspension integrity
     *
     * @param  TagTeam  $tagTeam  The tag team to suspend
     * @param  Carbon|null  $suspensionDate  The suspension start date (defaults to now)
     *
     * @example
     * ```php
     * // Suspend tag team immediately
     * $tagTeam = TagTeam::where('name', 'D-Generation X')->first();
     * SuspendAction::run($tagTeam);
     *
     * // Schedule suspension for future date
     * SuspendAction::run($tagTeam, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $suspensionDate = null): void
    {
        $tagTeam->ensureCanBeSuspended();

        $suspensionDate = $suspensionDate ?? now();

        DB::transaction(function () use ($tagTeam, $suspensionDate): void {
            // Create the suspension record for the tag team
            $tagTeam->suspensions()->create([
                'started_at' => $suspensionDate,
                'ended_at' => null,
            ]);

            // Suspend current wrestlers who are employed and not already suspended
            $wrestlersToSuspend = $tagTeam->currentWrestlers()
                ->get()
                ->filter(fn (Wrestler $wrestler) => $wrestler->isEmployed() && ! $wrestler->isSuspended());

            $wrestlersToSuspend->each(function (Wrestler $wrestler) use ($suspensionDate) {
                $this->wrestlersSuspendAction->handle($wrestler, $suspensionDate);
            });

            // Suspend current managers who are employed and not already suspended
            $managersToSuspend = $tagTeam->currentManagers()
                ->get()
                ->filter(fn (Manager $manager) => $manager->isEmployed() && ! $manager->isSuspended());

            $managersToSuspend->each(function (Manager $manager) use ($suspensionDate) {
                $this->managersSuspendAction->handle($manager, $suspensionDate);
            });
        });
    }
}
