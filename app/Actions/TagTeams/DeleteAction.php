<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Delete a tag team.
     */
    public function handle(TagTeam $tagTeam): void
    {
        $this->tagTeamRepository->delete($tagTeam);
    }
}
