<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\SuspendAction as WrestlerSuspendAction;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class SuspendAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Suspend a tag team.
     *
     * @param  \App\Models\TagTeam  $tagTeam
     * @param  \Illuminate\Support\Carbon|null  $suspensionDate
     * @return void
     */
    public function handle(TagTeam $tagTeam, ?Carbon $suspensionDate = null): void
    {
        $suspensionDate ??= now();

        $tagTeam->currentWrestlers->each(fn ($wrestler) =>  WrestlerSuspendAction::run($wrestler, $suspensionDate));

        $this->tagTeamRepository->suspend($tagTeam, $suspensionDate);
    }
}
