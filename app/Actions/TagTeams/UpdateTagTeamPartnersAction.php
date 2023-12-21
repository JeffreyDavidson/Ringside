<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateTagTeamPartnersAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Update a given tag team with given wrestlers.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Wrestler>  $wrestlers
     */
    public function handle(TagTeam $tagTeam, Collection $wrestlers, ?Carbon $joinDate = null): void
    {
        $joinDate ??= now();

        if ($tagTeam->currentWrestlers->isEmpty()) {
            if ($wrestlers->isNotEmpty()) {
                $this->tagTeamRepository->addWrestlers($tagTeam, $wrestlers, $joinDate);
            }
        } else {
            /** @var Collection<int, \App\Models\Wrestler> $formerTagTeamPartners */
            $formerTagTeamPartners = $tagTeam->currentWrestlers()->wherePivotNotIn(
                'wrestler_id',
                $wrestlers->modelKeys()
            )->get();

            $newTagTeamPartners = $wrestlers->except($formerTagTeamPartners->modelKeys());

            $this->tagTeamRepository->syncTagTeamPartners($tagTeam, $formerTagTeamPartners, $newTagTeamPartners, $joinDate);
        }
    }
}
