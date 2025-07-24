<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Manager;
use App\Models\Stables\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class AddMembersAction extends BaseStableAction
{
    use AsAction;

    /**
     * Add members to a given stable.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  Collection<int, TagTeam>  $tagTeams
     * @param  Collection<int, Manager>  $managers
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
