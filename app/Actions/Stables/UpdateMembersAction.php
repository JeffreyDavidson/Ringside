<?php

namespace App\Actions\Stables;

use App\Models\Stable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateMembersAction extends BaseStableAction
{
    use AsAction;

    /**
     * Retire a stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Database\Eloquent\Collection  $wrestlers
     * @param  \Illuminate\Database\Eloquent\Collection  $tagTeams
     *
     * @return void
     */
    public function handle(Stable $stable, Collection $wrestlers, Collection $tagTeams): void
    {
        $now = now();

        $this->updateWrestlers($stable, $wrestlers, $now);

        $this->updateTagTeams($stable, $tagTeams, $now);
    }

    /**
     * Undocumented function
     *
     * @param  \App\Models\Stable $stable
     * @param  \Illuminate\Database\Eloquent\Collection $wrestlers
     * @param  \Carbon\Carbon $now
     *
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
     * Undocumented function
     *
     * @param  \App\Models\Stable $stable
     * @param  \Illuminate\Database\Eloquent\Collection $tagTeams
     * @param  \Carbon\Carbon $now
     *
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
