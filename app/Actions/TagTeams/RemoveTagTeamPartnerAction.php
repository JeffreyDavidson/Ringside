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
     * Remove the wrestler from the tag team.
     */
    public function handle(TagTeam $tagTeam, Wrestler $wrestler, ?Carbon $removalDate = null): void
    {
        $removalDate ??= now();

        $this->tagTeamRepository->removeTagTeamPartner($tagTeam, $wrestler, $removalDate);
    }
}
