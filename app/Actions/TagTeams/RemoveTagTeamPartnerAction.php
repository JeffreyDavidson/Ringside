<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class RemoveTagTeamPartnerAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Update a given tag team with given wrestlers.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \App\Models\Wrestler  $wrestler
     * @return void
     */
    public function handle(TagTeam $tagTeam, Wrestler $wrestler, ?Carbon $removalDate = null): void
    {
        $removalDate ??= now();

        $this->tagTeamRepository->removeTagTeamPartner($tagTeam, $wrestler->id, $removalDate);
    }
}
