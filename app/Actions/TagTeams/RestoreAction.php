<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Restore a tag team.
     */
    public function handle(TagTeam $tagTeam): void
    {
        $this->tagTeamRepository->restore($tagTeam);
    }
}
