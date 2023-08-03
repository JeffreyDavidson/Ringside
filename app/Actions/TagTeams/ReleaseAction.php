<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\ReleaseAction as WrestlersReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReleaseAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Release a tag team.
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    public function handle(TagTeam $tagTeam, Carbon $releaseDate = null): void
    {
        $this->ensureCanBeReleased($tagTeam);

        $releaseDate ??= now();

        if ($tagTeam->isSuspended()) {
            ReinstateAction::run($tagTeam, $releaseDate);
        }

        $this->tagTeamRepository->release($tagTeam, $releaseDate);

        $tagTeam->currentWrestlers->each(fn ($wrestler) => WrestlersReleaseAction::run($wrestler, $releaseDate));
    }

    /**
     * Ensure a tag team can be released.
     *
     * @throws \App\Exceptions\CannotBeReleasedException
     */
    private function ensureCanBeReleased(TagTeam $tagTeam): void
    {
        if ($tagTeam->isUnemployed()) {
            throw CannotBeReleasedException::unemployed($tagTeam);
        }

        if ($tagTeam->hasFutureEmployment()) {
            throw CannotBeReleasedException::hasFutureEmployment($tagTeam);
        }
    }
}
