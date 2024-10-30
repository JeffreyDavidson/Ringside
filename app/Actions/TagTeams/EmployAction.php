<?php

declare(strict_types=1);

namespace App\Actions\TagTeams;

use App\Actions\Wrestlers\EmployAction as WrestlersEmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Models\TagTeam;
use App\Models\Wrestler;
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
        $this->ensureCanBeEmployed($tagTeam);

        $startDate ??= now();

        $tagTeam->currentWrestlers->each(fn (Wrestler $wrestler) => WrestlersEmployAction::run($wrestler, $startDate));

        $this->tagTeamRepository->employ($tagTeam, $startDate);
    }

    /**
     * Ensure a tag team can be employed.
     *
     * @throws \App\Exceptions\CannotBeEmployedException
     */
    private function ensureCanBeEmployed(TagTeam $tagTeam): void
    {
        if ($tagTeam->isCurrentlyEmployed()) {
            throw CannotBeEmployedException::employed();
        }
    }
}
