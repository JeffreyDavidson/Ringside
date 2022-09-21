<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateMembersAction extends BaseStableAction
{
    use AsAction;

    /**
     * Update a stables members.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler>  $wrestlers
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam>  $tagTeams
     * @return void
     */
    public function handle(Stable $stable, Collection $wrestlers, Collection $tagTeams): void
    {
        $now = now();

        $this->updateWrestlers($stable, $wrestlers, $now);
        $this->updateTagTeams($stable, $tagTeams, $now);
    }

    /**
     * Update wrestlers attached to a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Wrestler>  $wrestlers
     * @param  \Illuminate\Support\Carbon  $now
     * @return void
     */
    protected function updateWrestlers(Stable $stable, Collection $wrestlers, Carbon $now)
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
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagTeam>  $tagTeams
     * @param  \Illuminate\Support\Carbon  $now
     * @return void
     */
    protected function updateTagTeams(Stable $stable, Collection $tagTeams, Carbon $now)
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
}
