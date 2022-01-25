<?php

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateTagTeamPartnersAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Update a given tag team with given wrestlers.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \Illuminate\Database\Eloquent\Collection $wrestlers
     *
     * @return void
     */
    public function handle(TagTeam $tagTeam, Collection $wrestlers): void
    {
        if ($tagTeam->currentWrestlers->isEmpty()) {
            if ($wrestlers->isNotEmpty()) {
                $this->tagTeamRepository->addWrestlers($tagTeam, $wrestlers);
            }
        } else {
            $formerTagTeamPartners = $tagTeam->currentWrestlers()->wherePivotIn(
                'wrestler_id',
                $wrestlers->modelKeys()
            )->get();

            $newTagTeamPartners = $tagTeam->currentWrestlers()->wherePivotNotIn(
                'wrestler_id',
                $wrestlers->modelKeys()
            )->get();

            $this->tagTeamRepository->syncTagTeamPartners($tagTeam, $formerTagTeamPartners, $newTagTeamPartners);
        }
    }
}
