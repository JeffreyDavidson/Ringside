<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\EmployAction as WrestlersEmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Models\TagTeam;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class EmployAction extends BaseTagTeamAction
{
    use AsAction;

    /**
     * Employ a tag team.
     *
     * @throws \App\Exceptions\CannotBeEmployedException
     */
    public function handle(TagTeam $tagTeam, ?Carbon $startDate = null): void
    {
        throw_if($tagTeam->canBeEmployed(), CannotBeEmployedException::class);

        $startDate ??= now();

        $tagTeam->currentWrestlers->each(fn ($wrestler) => WrestlersEmployAction::run($wrestler, $startDate));

        $this->tagTeamRepository->employ($tagTeam, $startDate);
    }
}
