<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\UnretireAction as WrestlersUnretireAction;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class UnretireAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Unretire a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \Illuminate\Support\Carbon|null  $unretiredDate
     * @return void
     */
    public function handle(TagTeam $tagTeam, ?Carbon $unretiredDate = null): void
    {
        $unretiredDate ??= now();

        $this->tagTeamRepository->unretire($tagTeam, $unretiredDate);

        $tagTeam->currentWrestlers->each(fn ($wrestler) =>  WrestlersUnretireAction::run($wrestler, $unretiredDate));

        $this->tagTeamRepository->employ($tagTeam, $unretiredDate);
    }
}
