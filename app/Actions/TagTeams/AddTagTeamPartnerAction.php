<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use App\Models\Wrestler;
use Lorisleiva\Actions\Concerns\AsAction;

class AddTagTeamPartnerAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Update a given tag team with given wrestlers.
     */
    public function handle(TagTeam $tagTeam, Wrestler $wrestler): void
    {
        $this->tagTeamRepository->addTagTeamPartner($tagTeam, $wrestler->id, now());
    }
}
