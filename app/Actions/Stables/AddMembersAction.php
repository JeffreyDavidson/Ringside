<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class AddMembersAction extends BaseStableAction
{
    use AsAction;

    /**
     * Add members to a given stable.
     *
     * @param  Collection<int, \App\Models\Wrestler>  $wrestlers
     * @param  Collection<int, \App\Models\TagTeam>  $tagTeams
     * @param  Collection<int, \App\Models\Manager>  $managers
     */
    public function handle(
        Stable $stable,
        Collection $wrestlers,
        Collection $tagTeams,
        Collection $managers,
        ?Carbon $joinedDate = null
    ): void {
        $joinedDate ??= now();

        if ($wrestlers->isNotEmpty()) {
            $this->stableRepository->addWrestlers($stable, $wrestlers, $joinedDate);
        }

        if ($tagTeams->isNotEmpty()) {
            $this->stableRepository->addTagTeams($stable, $tagTeams, $joinedDate);
        }

        if ($managers->isNotEmpty()) {
            $this->stableRepository->addManagers($stable, $managers, $joinedDate);
        }
    }
}
