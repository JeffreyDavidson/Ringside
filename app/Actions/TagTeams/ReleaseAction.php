<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\ReleaseAction as ManagersReleaseAction;
use App\Actions\Wrestlers\ReleaseAction as WrestlersReleaseAction;
use App\Exceptions\Roster\CannotBeReleasedException;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Support\DateHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction
{
    use AsAction;

    /**
     * Create a new release action instance.
     */
    public function __construct(
        protected WrestlersReleaseAction $wrestlersReleaseAction,
        protected ManagersReleaseAction $managersReleaseAction
    ) {}

    /**
     * Release a tag team from employment and end all current relationships.
     *
     * This handles the complete tag team release workflow with cascading effects:
     * - Validates the tag team can be released (currently employed)
     * - Ends current wrestler partnerships (wrestlers become free agents)
     * - Ends current manager relationships
     * - Ends suspension if active
     * - Ends employment period with the specified date
     * - Maintains all historical records for tracking purposes
     * - Individual members may be re-hired independently
     *
     * @param  TagTeam  $tagTeam  The tag team to release
     * @param  Carbon|null  $releaseDate  The release date (defaults to now)
     * @throws CannotBeReleasedException When tag team cannot be released due to business rules
     *
     * @example
     * ```php
     * // Release tag team immediately
     * $tagTeam = TagTeam::where('name', 'The Shield')->first();
     * ReleaseAction::run($tagTeam);
     *
     * // Release with specific date
     * ReleaseAction::run($tagTeam, Carbon::parse('2024-12-31'));
     * ```
     */
    public function handle(TagTeam $tagTeam, ?Carbon $releaseDate = null): void
    {
        $tagTeam->ensureCanBeReleased();

        $releaseDate = DateHelper::resolveDate($releaseDate);

        DB::transaction(function () use ($tagTeam, $releaseDate): void {
            // End suspension if active
            if ($tagTeam->isSuspended()) {
                $tagTeam->suspensions()->where('ended_at', null)->update(['ended_at' => $releaseDate]);
            }

            // End current wrestler partnerships (wrestlers become free agents)
            $tagTeam->currentWrestlers->each(function (Wrestler $wrestler) use ($tagTeam, $releaseDate) {
                $tagTeam->wrestlers()->updateExistingPivot($wrestler->id, [
                    'left_at' => $releaseDate,
                ]);
            });

            // End current manager relationships
            $tagTeam->currentManagers->each(function (Manager $manager) use ($tagTeam, $releaseDate) {
                $tagTeam->managers()->updateExistingPivot($manager->id, [
                    'fired_at' => $releaseDate,
                ]);
            });

            // End tag team employment
            $tagTeam->employments()->where('ended_at', null)->update(['ended_at' => $releaseDate]);
        });
    }
}
