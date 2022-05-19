<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class AddMembersAction extends BaseStableAction
{
    use AsAction;

    /**
     * Add members to a given stable.
     *
     * @param  \App\Models\Stable  $stable
     * @param  \Illuminate\Database\Eloquent\Collection  $wrestlers
     * @param  \Illuminate\Database\Eloquent\Collection  $tagTeams
     * @param  \Illuminate\Support\Carbon|null  $joinedDate
     * @return void
     */
    public function handle(
        Stable $stable,
        Collection $wrestlers,
        Collection $tagTeams,
        ?Carbon $joinedDate = null
    ): void {
        $joinedDate ??= now();

        if ($wrestlers->isNotEmpty()) {
            $this->stableRepository->addWrestlers($stable, $wrestlers, $joinedDate);
        }

        if ($tagTeams->isNotEmpty()) {
            $this->stableRepository->addTagTeams($stable, $tagTeams, $joinedDate);
        }
    }
}
