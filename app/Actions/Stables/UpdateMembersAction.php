<?php

namespace App\Actions\Stables;

use App\Models\Stable;
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

        if ($stable->currentWrestlers->isEmpty()) {
            $this->stableRepository->addWrestlers($stable, $wrestlers, $now);
        } else {
            $currentWrestlers = $stable->currentWrestlers;
            $formerWrestlers = $currentWrestlers->diff($wrestlers);
            $newWrestlers = $wrestlers->diff($currentWrestlers);

            $this->stableRepository->removeWrestlers($stable, $formerWrestlers, $now);
            $this->stableRepository->addWrestlers($stable, $newWrestlers, $now);
        }

        if ($stable->currentTagTeams->isEmpty()) {
            $this->stableRepository->addTagTeams($stable, $tagTeams, $now);
        } else {
            $currentTagTeams = $stable->currentTagTeams;
            $formerTagTeams = $currentTagTeams->diff($tagTeams);
            $newTagTeams = $wrestlers->diff($currentTagTeams);

            $this->stableRepository->removeTagTeams($stable, $formerTagTeams, $now);
            $this->stableRepository->addTagTeams($stable, $newTagTeams, $now);
        }
    }
}
