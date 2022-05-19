<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class AddTagTeamPartnersAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Update a given tag team with given wrestlers.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \Illuminate\Database\Eloquent\Collection $wrestlers
     * @return void
     */
    public function handle(TagTeam $tagTeam, Collection $wrestlers): void
    {
        $this->tagTeamRepository->addWrestlers($tagTeam, $wrestlers, now());
    }
}
