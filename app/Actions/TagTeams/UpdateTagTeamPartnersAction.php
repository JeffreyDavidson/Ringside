<?php

declare(strict_types=1);

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
     * @param  \Illuminate\Database\Eloquent\Collection  $wrestlers
     * @return void
     */
    public function handle(TagTeam $tagTeam, Collection $wrestlers): void
    {
        if ($tagTeam->currentWrestlers->isEmpty()) {
            if ($wrestlers->isNotEmpty()) {
                $this->tagTeamRepository->addWrestlers($tagTeam, $wrestlers);
            }
        } else {
            $formerTagTeamPartners = $tagTeam->currentWrestlers()->wherePivotNotIn(
                'wrestler_id',
                $wrestlers->modelKeys()
            )->get();

            $newTagTeamPartners = $wrestlers->except($formerTagTeamPartners->modelKeys());

            $this->tagTeamRepository->syncTagTeamPartners($tagTeam, $formerTagTeamPartners, $newTagTeamPartners);
        }
    }
}
