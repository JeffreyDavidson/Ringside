<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Managers\ReleaseAction as ManagersReleaseAction;
use App\Actions\Wrestlers\ReleaseAction as WrestlersReleaseAction;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\TagTeamRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Create a new release action instance.
     */
    public function __construct(
        protected TagTeamRepository $tagTeamRepository,
        protected WrestlersReleaseAction $wrestlersReleaseAction,
        protected ManagersReleaseAction $managersReleaseAction
    ) {
        parent::__construct($tagTeamRepository);
    }

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
     *
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

        $releaseDate = $this->getEffectiveDate($releaseDate);

        DB::transaction(function () use ($tagTeam, $releaseDate): void {
            // End suspension if active
            if ($tagTeam->isSuspended()) {
                $this->tagTeamRepository->endSuspension($tagTeam, $releaseDate);
            }

            // End current wrestler partnerships (wrestlers become free agents)
            $this->tagTeamRepository->removeWrestlers($tagTeam, $tagTeam->currentWrestlers, $releaseDate);

            // End current manager relationships
            $this->tagTeamRepository->removeManagers($tagTeam, $tagTeam->currentManagers, $releaseDate);

            // End tag team employment
            $this->tagTeamRepository->endEmployment($tagTeam, $releaseDate);
        });
    }
}
