<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Manager;
use App\Models\Stables\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateMembersAction extends BaseStableAction
{
    use AsAction;

    /**
     * Update a stable's members.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  Collection<int, TagTeam>  $tagTeams
     * @param  Collection<int, Manager>  $managers
     */
    public function handle(Stable $stable, Collection $wrestlers, Collection $tagTeams, Collection $managers): void
    {
        $now = now();

        $this->updateWrestlers($stable, $wrestlers, $now);
        $this->updateTagTeams($stable, $tagTeams, $now);
        $this->updateManagers($stable, $managers, $now);
    }

    /**
     * Update wrestlers attached to a stable.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     */
    private function updateWrestlers(Stable $stable, Collection $wrestlers, Carbon $now): void
    {
        if ($stable->currentWrestlers->isEmpty()) {
            $this->stableRepository->addWrestlers($stable, $wrestlers, $now);
        } else {
            $currentWrestlers = $stable->currentWrestlers;
            $formerWrestlers = $currentWrestlers->diff($wrestlers);
            $newWrestlers = $wrestlers->diff($currentWrestlers);

            $this->stableRepository->removeWrestlers($stable, $formerWrestlers, $now);
            $this->stableRepository->addWrestlers($stable, $newWrestlers, $now);
        }
    }

    /**
     * Update tag teams attached to a stable.
     *
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    private function updateTagTeams(Stable $stable, Collection $tagTeams, Carbon $now): void
    {
        if ($stable->currentTagTeams->isEmpty()) {
            $this->stableRepository->addTagTeams($stable, $tagTeams, $now);
        } else {
            $currentTagTeams = $stable->currentTagTeams;
            $formerTagTeams = $currentTagTeams->diff($tagTeams);
            $newTagTeams = $tagTeams->diff($currentTagTeams);

            $this->stableRepository->removeTagTeams($stable, $formerTagTeams, $now);
            $this->stableRepository->addTagTeams($stable, $newTagTeams, $now);
        }
    }

    /**
     * Update managers attached to a stable.
     *
     * @param  Collection<int, Manager>  $managers
     */
    private function updateManagers(Stable $stable, Collection $managers, Carbon $now): void
    {
        if ($stable->currentManagers->isEmpty()) {
            $this->stableRepository->addManagers($stable, $managers, $now);
        } else {
            $currentManagers = $stable->currentManagers;
            $formerManagers = $currentManagers->diff($managers);
            $newManagers = $managers->diff($currentManagers);

            $this->stableRepository->removeManagers($stable, $formerManagers, $now);
            $this->stableRepository->addManagers($stable, $newManagers, $now);
        }
    }
}
