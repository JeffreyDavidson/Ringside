<?php

declare(strict_types=1);

namespace App\Actions\Stables;

use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

class AddMembersAction
{
    use AsAction;

    /**
     * Add members to a given stable.
     *
     * @param  Collection<int, Wrestler>  $wrestlers
     * @param  Collection<int, TagTeam>  $tagTeams
     */
    public function handle(
        Stable $stable,
        Collection $wrestlers,
        Collection $tagTeams,
        ?Carbon $joinedDate = null
    ): void {
        $joinedDate = $joinedDate ?? now();

        if ($wrestlers->isNotEmpty()) {
            foreach ($wrestlers as $wrestler) {
                $stable->wrestlers()->attach($wrestler->id, [
                    'joined_at' => $joinedDate,
                    'left_at' => null,
                ]);
            }
        }

        if ($tagTeams->isNotEmpty()) {
            foreach ($tagTeams as $tagTeam) {
                $stable->tagTeams()->attach($tagTeam->id, [
                    'joined_at' => $joinedDate,
                    'left_at' => null,
                ]);
            }
        }
    }
}
