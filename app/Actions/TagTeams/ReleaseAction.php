<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\ReleaseAction as WrestlersReleaseAction;
use App\Models\TagTeam;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Release a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @return void
     */
    public function handle(TagTeam $tagTeam): void
    {
        $releaseDate = now();

        if ($tagTeam->isSuspended()) {
            ReinstateAction::run($tagTeam, $releaseDate);
        }

        $this->tagTeamRepository->release($tagTeam, $releaseDate);
        $tagTeam->save();

        $tagTeam->currentWrestlers->each(function ($wrestler) use ($releaseDate) {
            WrestlersReleaseAction::run($wrestler, $releaseDate);
        });
    }
}
