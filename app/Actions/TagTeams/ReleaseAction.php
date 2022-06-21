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
     * @param  \Illuminate\Support\Carbon|null  $releaseDate
     * @return void
     */
    public function handle(TagTeam $tagTeam, ?Carbon $releaseDate = null): void
    {
        $releaseDate ??= now();

        if ($tagTeam->isSuspended()) {
            ReinstateAction::run($tagTeam, $releaseDate);
        }

        $this->tagTeamRepository->release($tagTeam, $releaseDate);

        $tagTeam->currentWrestlers->each(fn ($wrestler) => WrestlersReleaseAction::run($wrestler, $releaseDate));
    }
}
