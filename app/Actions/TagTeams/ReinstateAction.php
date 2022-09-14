<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\ReinstateAction as WrestlersReinstateAction;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class ReinstateAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Reinstate a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \Illuminate\Support\Carbon|null  $reinstatementDate
     * @return void
     *
     * @throws \App\Exceptions\CannotBeReinstatedException
     */
    public function handle(TagTeam $tagTeam, ?Carbon $reinstatementDate = null): void
    {
        throw_if($tagTeam->canBeReinstated(), CannotBeReinstatedException::class);

        $reinstatementDate ??= now();

        $tagTeam->currentWrestlers
            ->each(fn ($wrestler) => WrestlersReinstateAction::run($wrestler, $reinstatementDate));

        $this->tagTeamRepository->reinstate($tagTeam, $reinstatementDate);
    }
}
